<?php

namespace App\Modules\Marketing\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Marketing\Models\MarketingCampaign;
use App\Modules\Marketing\Models\MarketingCampaignProduct;
use App\Modules\Product\Models\Product;
use App\Services\Promotion\ProductStockValidatorInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Validator;

class MarketingCampaignController extends Controller
{
    private $model;
    private $view = 'Marketing';
    protected ProductStockValidatorInterface $productStockValidator;

    public function __construct(MarketingCampaign $model, ProductStockValidatorInterface $productStockValidator)
    {
        $this->model = $model;
        $this->productStockValidator = $productStockValidator;
    }

    public function index(Request $request)
    {
        active('marketing', 'campaign');
        $data['list'] = $this->model::where(function ($query) use ($request) {
            if ($request->get('status') != "") {
                $query->where('status', $request->get('status'));
            }
            if ($request->get('keyword') != "") {
                $query->where('name', 'like', '%' . $request->get('keyword') . '%');
            }
        })->latest()->paginate(10)->appends($request->query());
        return view($this->view . '::index', $data);
    }

    public function create()
    {
        active('marketing', 'campaign');
        // Do NOT load all products here to avoid performance issues
        return view($this->view . '::create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'start_at' => 'required',
            'end_at' => 'required',
        ], [
            'name.required' => 'Tên chương trình không được bỏ trống.',
            'start_at.required' => 'Thời gian bắt đầu không được bỏ trống.',
            'end_at.required' => 'Thời gian kết thúc không được bỏ trống',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        $start = \Carbon\Carbon::parse($request->start_at);
        $end = \Carbon\Carbon::parse($request->end_at);

        if ($start >= $end) {
             return response()->json([
                'status' => 'error',
                'errors' => ['end_at' => ['Thời gian kết thúc phải sau thời gian bắt đầu']]
            ]);
        }
        
        $id = $this->model::insertGetId([
            'name' => $request->name,
            'start_at' => $request->start_at,
            'end_at' => $request->end_at,
            'status' => $request->status,
            'user_id' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($id > 0) {
            $pricesale = $request->pricesale;

            if (isset($pricesale) && !empty($pricesale)) {
                foreach ($pricesale as $productId => $price) {
                    if ($this->checkProductOverlap($productId, $start, $end)) {
                        continue; 
                    }

                    // Validate product stock > 0
                    $stock = $this->productStockValidator->getProductStock($productId);
                    if ($stock <= 0) {
                        $product = Product::find($productId);
                        $productName = $product ? $product->name : "ID {$productId}";
                        Log::warning("Product has no stock, skipped from MarketingCampaign", [
                            'product_id' => $productId,
                            'product_name' => $productName,
                            'campaign_id' => $id,
                            'stock' => $stock
                        ]);
                        continue; // Skip this product
                    }

                    MarketingCampaignProduct::create([
                        'campaign_id' => $id,
                        'product_id' => $productId,
                        'price' => ($price != "") ? str_replace(',', '', $price) : 0,
                        'limit' => 0
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Tạo chương trình thành công!',
                'url' => route('marketing.campaign.index')
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'errors' => array('alert' => array('0' => 'Tạo không thành công!'))
            ]);
        }
    }

    public function edit($id)
    {
        active('marketing', 'campaign');
        $detail = $this->model::find($id);
        if (!$detail) {
            return redirect()->route('marketing.campaign.index');
        }
        $data['detail'] = $detail;
        $data['campaign_products'] = MarketingCampaignProduct::where('campaign_id', $id)->get();
        
        // Load products with actual stock for display
        $productIds = $data['campaign_products']->pluck('product_id')->unique();
        $products = Product::whereIn('id', $productIds)
            ->with(['variants' => function($q) {
                $q->select('id', 'product_id', 'option1_value', 'price', 'stock', 'sku');
            }])
            ->get();
        
        // Calculate actual stock for each product
        $productsWithStock = $products->map(function($product) {
            $product->actual_stock = $this->productStockValidator->getProductStock($product->id);
            return $product;
        });
        
        $data['products'] = $productsWithStock;
        
        return view($this->view . '::edit', $data);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'start_at' => 'required',
            'end_at' => 'required',
        ], [
            'name.required' => 'Tên chương trình không được bỏ trống.',
            'start_at.required' => 'Thời gian bắt đầu không được bỏ trống.',
            'end_at.required' => 'Thời gian kết thúc không được bỏ trống',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        $start = \Carbon\Carbon::parse($request->start_at);
        $end = \Carbon\Carbon::parse($request->end_at);

         if ($start >= $end) {
             return response()->json([
                'status' => 'error',
                'errors' => ['end_at' => ['Thời gian kết thúc phải sau thời gian bắt đầu']]
            ]);
        }

        // Check overlap if status is being set to Active (1)
        if ($request->status == 1) {
            // Get all products currently in this campaign (from DB, before update? No, we might be adding/removing)
            // Actually, we are updating the campaign details first.
            // But we haven't updated products yet.
            // If we activate the campaign, we need to ensure its EXISTING products don't overlap.
            // AND the NEW products don't overlap.
            
            // For simplicity: Check overlap for ALL products involved (existing + new).
            // But we haven't processed the product list yet.
            // This is complex because we do update in 2 steps: Campaign info, then Products.
            
            // Let's do the check inside the product loop? 
            // BUT what about existing products that are NOT in the submitted list (will be deleted)?
            // What about existing products that ARE in the list (will be kept)?
            
            // If we update campaign to Active, we must validate matching products.
            // Since we can't easily rollback the status update if products fail later in the loop (unless transactions).
            // Let's use DB Transaction.
        }

        \DB::beginTransaction();
        try {
            $up = $this->model::where('id', $request->id)->update([
                'name' => $request->name,
                'start_at' => $request->start_at,
                'end_at' => $request->end_at,
                'status' => $request->status,
                'user_id' => Auth::id(),
                'updated_at' => now(),
            ]);

            if ($up) {
            $pricesale = $request->pricesale ?? [];
            $checklist = $request->checklist ?? []; // IDs of selected products (from the main table)
            
            Log::info('[MarketingCampaign] Update request data', [
                'campaign_id' => $request->id,
                'pricesale_count' => count($pricesale),
                'checklist_count' => count($checklist),
                'pricesale_keys' => array_keys($pricesale),
                'checklist' => $checklist,
            ]);
            
            // Get all product IDs that should be in the campaign (from pricesale, as it's the source of truth)
            $productIdsToKeep = array_keys($pricesale);
            
            // Delete removed products (products that were in campaign but not in submitted pricesale)
            if (!empty($productIdsToKeep)) {
                MarketingCampaignProduct::where('campaign_id', $request->id)
                    ->whereNotIn('product_id', $productIdsToKeep)
                    ->delete();
            } else {
                // If pricesale is empty, delete all products from campaign
                MarketingCampaignProduct::where('campaign_id', $request->id)->delete();
            }

            // Process each product in pricesale
            if (!empty($pricesale)) {
                foreach ($pricesale as $productId => $price) {
                    // Skip if productId is not numeric (could be array key issue)
                    if (!is_numeric($productId)) {
                        Log::warning('[MarketingCampaign] Invalid product ID in pricesale', [
                            'campaign_id' => $request->id,
                            'product_id' => $productId,
                            'price' => $price,
                        ]);
                        continue;
                    }
                    
                    $productId = (int) $productId;
                    
                    // Clean price value (remove commas, spaces, dots from number formatting)
                    $cleanPrice = ($price != "" && $price !== null) ? str_replace([',', ' '], '', $price) : 0;
                    $cleanPrice = (float) $cleanPrice;
                    
                    if ($cleanPrice <= 0) {
                        Log::warning('[MarketingCampaign] Invalid price, skipping product', [
                            'campaign_id' => $request->id,
                            'product_id' => $productId,
                            'raw_price' => $price,
                            'clean_price' => $cleanPrice,
                        ]);
                        continue;
                    }
                    
                    // Check if product already exists in campaign (query AFTER delete to avoid stale data)
                    $exists = MarketingCampaignProduct::where('campaign_id', $request->id)
                        ->where('product_id', $productId)
                        ->first();
                    
                    if ($exists) {
                        // Update existing product price
                        $oldPrice = $exists->price;
                        $exists->update([
                            'price' => $cleanPrice,
                        ]);
                        Log::info('[MarketingCampaign] Updated existing product price', [
                            'campaign_id' => $request->id,
                            'product_id' => $productId,
                            'old_price' => $oldPrice,
                            'new_price' => $cleanPrice,
                        ]);
                    } else {
                        // Create new product in campaign
                        // Check overlap first
                        if ($this->checkProductOverlap($productId, $start, $end, $request->id)) {
                            Log::warning('[MarketingCampaign] Product overlap detected, skipped', [
                                'campaign_id' => $request->id,
                                'product_id' => $productId,
                            ]);
                            continue; 
                        }

                        // Validate product stock > 0 (only for new products)
                        $stock = $this->productStockValidator->getProductStock($productId);
                        if ($stock <= 0) {
                            $product = Product::find($productId);
                            $productName = $product ? $product->name : "ID {$productId}";
                            Log::warning('[MarketingCampaign] Product has no stock, skipped', [
                                'product_id' => $productId,
                                'product_name' => $productName,
                                'campaign_id' => $request->id,
                                'stock' => $stock
                            ]);
                            continue; // Skip this product
                        }

                        try {
                            MarketingCampaignProduct::create([
                                'campaign_id' => $request->id,
                                'product_id' => $productId,
                                'price' => $cleanPrice,
                                'limit' => 0
                            ]);
                            Log::info('[MarketingCampaign] Created new product in campaign', [
                                'campaign_id' => $request->id,
                                'product_id' => $productId,
                                'price' => $cleanPrice,
                            ]);
                        } catch (\Exception $e) {
                            Log::error('[MarketingCampaign] Failed to create product in campaign', [
                                'campaign_id' => $request->id,
                                'product_id' => $productId,
                                'price' => $cleanPrice,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);
                            // Continue processing other products even if one fails
                        }
                    }
                }
            } else {
                Log::warning('[MarketingCampaign] No pricesale data in request', [
                    'campaign_id' => $request->id,
                    'request_all' => $request->all(),
                ]);
            }

            \DB::commit();
            
            Log::info('[MarketingCampaign] Campaign updated successfully', [
                'campaign_id' => $request->id,
            ]);
            
            return response()->json([
                'status' => 'success',
                'alert' => 'Cập nhật thành công!',
                'url' => route('marketing.campaign.index')
            ]);
        } else {
            \DB::rollBack();
            Log::error('[MarketingCampaign] Campaign update failed - campaign not found or update returned false', [
                'campaign_id' => $request->id,
            ]);
            return response()->json([
                'status' => 'error',
                'errors' => array('alert' => array('0' => 'Cập nhật không thành công!'))
            ]);
        }
        
        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error('[MarketingCampaign] Exception during campaign update', [
                'campaign_id' => $request->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'status' => 'error',
                'errors' => array('alert' => array('0' => 'Có lỗi xảy ra: ' . $e->getMessage()))
            ]);
        }
    }

    public function delete(Request $request)
    {
        $this->model::findOrFail($request->id)->delete();
        MarketingCampaignProduct::where('campaign_id', $request->id)->delete();
        
        return response()->json([
            'status' => 'success',
            'alert' => 'Xóa thành công!',
            'url' => route('marketing.campaign.index')
        ]);
    }

    public function status(Request $request)
    {
        $this->model::where('id', $request->id)->update(['status' => $request->status]);
        return response()->json([
            'status' => 'success',
            'alert' => 'Đổi trạng thái thành công!',
            'url' => route('marketing.campaign.index')
        ]);
    }

    public function action(Request $request)
    {
        $check = $request->checklist;
        if (!isset($check) && empty($check)) {
            return response()->json([
                'status' => 'error',
                'errors' => array('alert' => array('0' => 'Chưa chọn dữ liệu cần thao tác!'))
            ]);
        }
        $action = $request->action;
        if ($action == 0) { // Hide
            $this->model::whereIn('id', $check)->update(['status' => 0]);
        } elseif ($action == 1) { // Show
            $this->model::whereIn('id', $check)->update(['status' => 1]);
        } else { // Delete
            $this->model::whereIn('id', $check)->delete();
            MarketingCampaignProduct::whereIn('campaign_id', $check)->delete();
        }

        return response()->json([
            'status' => 'success',
            'alert' => 'Thao tác thành công!',
            'url' => route('marketing.campaign.index')
        ]);
    }

    public function loadProduct(Request $request)
    {
        // Used when selecting products from modal to add to the table
        $products = Product::select('id', 'name', 'image', 'has_variants')
            ->whereIn('id', $request->productid)
            ->with(['variants' => function($q) {
                $q->select('id', 'product_id', 'option1_value', 'price', 'stock', 'sku');
            }])
            ->get();
        
        // Calculate actual stock for each product
        $productsWithStock = $products->map(function($product) {
            $product->actual_stock = $this->productStockValidator->getProductStock($product->id);
            return $product;
        });
        
        $data['products'] = $productsWithStock;
        // Return only the rows to be appended
        return view($this->view . '::product_rows', $data);
    }

    // NEW: Search Product for Modal via Ajax
    public function searchProduct(Request $request)
    {
        $keyword = $request->get('keyword');
        $products = Product::select('id', 'name', 'image', 'stock', 'has_variants')
            ->where([['status', '1'], ['type', 'product']])
            ->where('name', 'like', '%' . $keyword . '%')
            ->with(['variants' => function($q) {
                $q->select('id', 'product_id', 'option1_value', 'price', 'stock', 'sku');
            }])
            ->orderBy('id', 'desc')
            ->paginate(50); // Pagination to avoid "max_input_vars" issue and slowness

        // Return HTML to render in Modal Body
        $html = '';
        foreach($products as $product) {
            // Get actual stock from warehouse system
            $stock = $this->productStockValidator->getProductStock($product->id);
            
            // Filter out products with stock = 0
            if ($stock <= 0) {
                // Check if product has variants with stock > 0
                if ($product->has_variants == 1 && $product->variants) {
                    $hasStock = false;
                    $totalVariantStock = 0;
                    foreach ($product->variants as $variant) {
                        $variantStock = $this->productStockValidator->getProductStock(
                            $product->id,
                            $variant->id
                        );
                        if ($variantStock > 0) {
                            $hasStock = true;
                            $totalVariantStock += $variantStock;
                        }
                    }
                    // If no variants have stock, skip this product
                    if (!$hasStock) {
                        continue;
                    }
                    // Use total variant stock for display
                    $stock = $totalVariantStock;
                } else {
                    // Product has no variants and stock = 0, skip it
                    continue;
                }
            }
            
            $variant = $product->variant($product->id);
            $price = $variant ? number_format($variant->price) . 'đ' : '-';
            $image = getImage($product->image);
            
            $html .= '<tr>';
            $html .= '<td width="5%" style="text-align: center;">';
            $html .= '<input style="margin: 0px;display: inline-block;" type="checkbox" name="productid[]" class="checkbox wgr-checkbox" value="'.$product->id.'">';
            $html .= '</td>';
            $html .= '<td width="40%">';
            $html .= '<img src="'.$image.'" style="width:50px;height: 50px;float: left;margin-right: 5px;">';
            $html .= '<p>'.$product->name.'</p>';
            $html .= '</td>';
            $html .= '<td width="15%">'.$price.'</td>';
            $html .= '<td width="15%">-</td>'; // Placeholder
            $html .= '<td width="15%" style="text-align: center;"><strong>'.number_format($stock).'</strong></td>';
            $html .= '</tr>';
        }
        
        // If no products found, show message
        if (empty($html)) {
            $html = '<tr><td colspan="5" class="text-center">Không tìm thấy sản phẩm có tồn kho</td></tr>';
        }
        
        // Add pagination links if needed, but for simple scroll we might just load more? 
        // For now, simple pagination UI or just load 50 is better than 1000.
        
        return response()->json(['html' => $html]);
    }

    private function checkProductOverlap($productId, $start, $end, $excludeCampaignId = null)
    {
        $query = MarketingCampaignProduct::where('product_id', $productId)
            ->whereHas('campaign', function ($q) use ($start, $end, $excludeCampaignId) {
                $q->where('status', 1) 
                  ->where(function($q2) use ($start, $end) {
                      $q2->whereBetween('start_at', [$start, $end])
                         ->orWhereBetween('end_at', [$start, $end])
                         ->orWhere(function($q3) use ($start, $end) {
                             $q3->where('start_at', '<=', $start)
                                ->where('end_at', '>=', $end);
                         });
                  });
                if ($excludeCampaignId) {
                    $q->where('id', '!=', $excludeCampaignId);
                }
            });

        return $query->exists();
    }
}
