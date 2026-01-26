<?php

declare(strict_types=1);

namespace App\Modules\Deal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Brand\Models\Brand;
use App\Modules\Deal\Models\Deal;
use App\Modules\Deal\Models\ProductDeal;
use App\Modules\Deal\Models\SaleDeal;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Services\Inventory\InventoryServiceInterface;
use App\Services\Promotion\ProductStockValidatorInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Session;
use Validator;

class DealController extends Controller
{
    private $model;
    private $controller = 'deal';
    private $view = 'Deal';
    private $limit = 10;
    protected ProductStockValidatorInterface $productStockValidator;
    protected InventoryServiceInterface $inventoryService;

    public function __construct(Deal $model, ProductStockValidatorInterface $productStockValidator, InventoryServiceInterface $inventoryService)
    {
        $this->model = $model;
        $this->productStockValidator = $productStockValidator;
        $this->inventoryService = $inventoryService;
    }

    private function resolveVariantId(int $productId, ?int $variantId = null): int
    {
        if ($variantId !== null && $variantId > 0) {
            return (int) $variantId;
        }

        $fallbackVariantId = (int) Variant::query()->where('product_id', $productId)->value('id');
        if ($fallbackVariantId <= 0) {
            throw new \Exception("Variant not found for product_id: {$productId}");
        }

        return $fallbackVariantId;
    }

    private function releaseDealHoldsForDeal(int $dealId, int $limitedQty): void
    {
        $oldSaleDeals = SaleDeal::query()->where('deal_id', $dealId)->get();
        foreach ($oldSaleDeals as $sd) {
            $variantId = $this->resolveVariantId((int) $sd->product_id, $sd->variant_id ? (int) $sd->variant_id : null);
            $qty = (int) ($sd->qty ?? 0);
            $buy = (int) ($sd->buy ?? 0);
            $remaining = max(0, $qty - $buy);
            if ($remaining <= 0) {
                continue;
            }
            $res = $this->inventoryService->releaseStockFromPromotion($variantId, $remaining, 'deal');
            Log::info('[DEAL_HOLD_SYNC] release sale_deal hold', [
                'deal_id' => $dealId,
                'sale_deal_id' => $sd->id,
                'variant_id' => $variantId,
                'remaining' => $remaining,
                'result' => $res,
            ]);
        }

        $oldProductDeals = ProductDeal::query()->where('deal_id', $dealId)->get();
        foreach ($oldProductDeals as $pd) {
            $variantId = $this->resolveVariantId((int) $pd->product_id, $pd->variant_id ? (int) $pd->variant_id : null);
            if ($limitedQty <= 0) {
                continue;
            }
            $res = $this->inventoryService->releaseStockFromPromotion($variantId, $limitedQty, 'deal');
            Log::info('[DEAL_HOLD_SYNC] release main_product hold', [
                'deal_id' => $dealId,
                'product_deal_id' => $pd->id,
                'variant_id' => $variantId,
                'qty' => $limitedQty,
                'result' => $res,
            ]);
        }
    }

    private function allocateDealHoldsForDeal(int $dealId, int $limitedQty): void
    {
        $saleDeals = SaleDeal::query()->where('deal_id', $dealId)->get();
        foreach ($saleDeals as $sd) {
            $variantId = $this->resolveVariantId((int) $sd->product_id, $sd->variant_id ? (int) $sd->variant_id : null);
            $qty = (int) ($sd->qty ?? 0);
            $buy = (int) ($sd->buy ?? 0);
            $remaining = max(0, $qty - $buy);
            if ($remaining <= 0) {
                continue;
            }
            $res = $this->inventoryService->allocateStockForPromotion($variantId, $remaining, 'deal');
            Log::info('[DEAL_HOLD_SYNC] allocate sale_deal hold', [
                'deal_id' => $dealId,
                'sale_deal_id' => $sd->id,
                'variant_id' => $variantId,
                'remaining' => $remaining,
                'result' => $res,
            ]);
        }

        $productDeals = ProductDeal::query()->where('deal_id', $dealId)->get();
        foreach ($productDeals as $pd) {
            $variantId = $this->resolveVariantId((int) $pd->product_id, $pd->variant_id ? (int) $pd->variant_id : null);
            if ($limitedQty <= 0) {
                continue;
            }
            $res = $this->inventoryService->allocateStockForPromotion($variantId, $limitedQty, 'deal');
            Log::info('[DEAL_HOLD_SYNC] allocate main_product hold', [
                'deal_id' => $dealId,
                'product_deal_id' => $pd->id,
                'variant_id' => $variantId,
                'qty' => $limitedQty,
                'result' => $res,
            ]);
        }
    }

    public function index(Request $request)
    {
        active('marketing', 'deal');
        $data['list'] = $this->model::where(function ($query) use ($request) {
            if ($request->get('status') != '') {
                $query->where('status', $request->get('status'));
            }
            if ($request->get('keyword') != '') {
                $query->where('name', 'like', '%'.$request->get('keyword').'%');
            }
        })->latest()->paginate(20)->appends($request->query());

        // Calculate sales statistics for each deal
        $data['list']->getCollection()->transform(function ($deal) {
            $stats = SaleDeal::where('deal_id', $deal->id)
                ->selectRaw('SUM(qty) as total_qty, SUM(COALESCE(buy, 0)) as total_buy')
                ->first();

            $deal->total_qty = (int) ($stats->total_qty ?? 0);
            $deal->total_buy = (int) ($stats->total_buy ?? 0);
            $deal->total_remaining = $deal->total_qty - $deal->total_buy;
            $deal->sales_percentage = $deal->total_qty > 0
                ? round(($deal->total_buy / $deal->total_qty) * 100, 1)
                : 0;

            return $deal;
        });

        return view($this->view.'::index', $data);
    }

    public function create()
    {
        active('marketing', 'deal');
        Session::forget('ss_sale_product');
        Session::forget('ss_product_deal');

        return view($this->view.'::create');
    }

    public function edit($id)
    {
        active('marketing', 'deal');
        $detail = $this->model::find($id);
        if (! isset($detail) && empty($detail)) {
            return redirect()->route('deal');
        }
        $data['detail'] = $detail;
        $productdeals = ProductDeal::with(['product', 'variant'])->where('deal_id', $detail->id)->get();
        $data['productdeals'] = $productdeals;

        // Build session array with variant_id format: "product_id" or "product_id_vvariant_id"
        $productDealSession = [];
        foreach ($productdeals as $pd) {
            if ($pd->variant_id) {
                $productDealSession[] = $pd->product_id.'_v'.$pd->variant_id;
            } else {
                $productDealSession[] = $pd->product_id;
            }
        }
        Session::put('ss_product_deal', $productDealSession);

        $saledeals = SaleDeal::with(['product', 'variant'])->where('deal_id', $detail->id)->get();
        $data['saledeals'] = $saledeals;

        // Build session array with variant_id format: "product_id" or "product_id_vvariant_id"
        $saleDealSession = [];
        foreach ($saledeals as $sd) {
            if ($sd->variant_id) {
                $saleDealSession[] = $sd->product_id.'_v'.$sd->variant_id;
            } else {
                $saleDealSession[] = $sd->product_id;
            }
        }
        Session::put('ss_sale_product', $saleDealSession);

        // Load products with actual stock for display
        $productIds = $productdeals->pluck('product_id')->merge($saledeals->pluck('product_id'))->unique();
        $products = Product::whereIn('id', $productIds)
            ->with(['variants' => function ($q) {
                $q->select('id', 'product_id', 'option1_value', 'price', 'stock', 'sku');
            }])
            ->get();

        // Calculate actual stock for each product/variant
        $productsWithStock = $products->map(function ($product) {
            if ($product->has_variants == 1 && $product->variants) {
                // Product has variants - calculate stock for each variant
                $product->variants = $product->variants->map(function ($variant) use ($product) {
                    $variant->actual_stock = $this->productStockValidator->getProductStock(
                        $product->id,
                        $variant->id
                    );

                    return $variant;
                });
            } else {
                // Product without variants
                $product->actual_stock = $this->productStockValidator->getProductStock($product->id);
            }

            return $product;
        });

        $data['products'] = $productsWithStock;

        return view($this->view.'::edit', $data);
    }

    public function view($id)
    {
        active('marketing', 'deal');
        $detail = $this->model::find($id);
        if (! isset($detail) && empty($detail)) {
            return redirect()->route('deal');
        }
        $data['detail'] = $detail;
        $productdeals = ProductDeal::where('deal_id', $detail->id)->get();
        $data['productdeals'] = $productdeals;
        $saledeals = SaleDeal::where('deal_id', $detail->id)->get();
        $data['saledeals'] = $saledeals;

        return view($this->view.'::view', $data);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start' => 'required',
            'end' => 'required',
        ], [
            'start.required' => 'Thời gian bắt đầu không được bỏ trống.',
            'end.required' => 'Thời gian kết thúc không được bỏ trống',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ]);
        }
        $dealBefore = $this->model::find($request->id);
        $oldLimited = (int) ($dealBefore->limited ?? 0);

        $up = $this->model::where('id', $request->id)->update([
            'name' => $request->name,
            'start' => strtotime($request->start),
            'end' => strtotime($request->end),
            'status' => $request->status,
            'limited' => $request->limited,
            'user_id' => Auth::id(),
        ]);
        if ($up > 0) {
            try {
                // Release old holds first (based on old config)
                $this->releaseDealHoldsForDeal((int) $request->id, $oldLimited);
            } catch (\Exception $e) {
                Log::error('[DEAL_HOLD_SYNC] release failed on update', [
                    'deal_id' => (int) $request->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Validation: Check conflicts before saving
            $productid = $request->productid;
            $pricesale = $request->pricesale;
            $numbersale = $request->numbersale;
            $status2 = $request->status2;

            // Rule 1: Sản phẩm phụ không thể là sản phẩm chính trong cùng deal
            if (isset($pricesale) && ! empty($pricesale) && isset($productid) && ! empty($productid)) {
                $mainProductKeys = [];
                foreach ($productid as $productValue) {
                    $mainProductKeys[] = $productValue;
                }

                foreach ($pricesale as $key => $value) {
                    if (is_array($value)) {
                        foreach ($value as $vId => $price) {
                            $saleProductKey = $key.'_v'.$vId;
                            if (in_array($saleProductKey, $mainProductKeys)) {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => 'Sản phẩm "'.$this->getProductName($key, $vId).'" đã là sản phẩm chính, không thể thêm làm sản phẩm phụ!',
                                ]);
                            }
                        }
                    } else {
                        $saleProductKey = (string) $key;
                        if (in_array($saleProductKey, $mainProductKeys)) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Sản phẩm "'.$this->getProductName($key).'" đã là sản phẩm chính, không thể thêm làm sản phẩm phụ!',
                            ]);
                        }
                    }
                }
            }

            // Rule 2: Sản phẩm chính không thể là sản phẩm chính của deal khác đang active
            if (isset($productid) && ! empty($productid)) {
                $currentTime = time();
                $activeDeals = Deal::where('status', '1')
                    ->where(function ($q) use ($currentTime) {
                        $q->where('start', '<=', $currentTime)
                            ->where('end', '>=', $currentTime);
                    })
                    ->where('id', '!=', $request->id) // Exclude current deal
                    ->pluck('id');

                if ($activeDeals->count() > 0) {
                    $conflictProducts = ProductDeal::whereIn('deal_id', $activeDeals)
                        ->get();

                    foreach ($productid as $productValue) {
                        $productId = $productValue;
                        $variantId = null;

                        if (strpos($productValue, '_v') !== false) {
                            $parts = explode('_v', $productValue);
                            $productId = $parts[0];
                            $variantId = $parts[1];
                        }

                        foreach ($conflictProducts as $conflictProduct) {
                            if ($conflictProduct->product_id == $productId &&
                                $conflictProduct->variant_id == $variantId) {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => 'Sản phẩm "'.$this->getProductName($productId, $variantId).'" đã là sản phẩm chính của Deal khác đang hoạt động!',
                                ]);
                            }
                        }
                    }
                }
            }

            SaleDeal::where('deal_id', $request->id)->delete();
            if (isset($pricesale) && ! empty($pricesale)) {
                foreach ($pricesale as $key => $value) {
                    // Parse product_id and variant_id from key format: "product_id" or "product_id[variant_id]"
                    $productId = $key;
                    $variantId = null;

                    // Check if value is array format: product_id[variant_id]
                    if (is_array($value)) {
                        // This is nested array format
                        foreach ($value as $vId => $price) {
                            SaleDeal::insertGetId(
                                [
                                    'deal_id' => $request->id,
                                    'product_id' => $key,
                                    'variant_id' => $vId,
                                    'price' => ($price != '') ? str_replace(',', '', $price) : 0,
                                    'qty' => (isset($numbersale[$key][$vId]) && ! empty($numbersale[$key][$vId])) ? $numbersale[$key][$vId] : '0',
                                    'status' => (isset($status2[$key][$vId]) && isset($status2[$key][$vId])) ? $status2[$key][$vId] : '0',
                                    'created_at' => date('Y-m-d H:i:s'),
                                ]
                            );
                        }
                    } else {
                        // Old format: single product without variant
                        SaleDeal::insertGetId(
                            [
                                'deal_id' => $request->id,
                                'product_id' => $key,
                                'variant_id' => null,
                                'price' => ($value != '') ? str_replace(',', '', $value) : 0,
                                'qty' => (isset($numbersale) && ! empty($numbersale)) ? $numbersale[$key] : '0',
                                'status' => (isset($status2) && isset($status2[$key])) ? $status2[$key] : '0',
                                'created_at' => date('Y-m-d H:i:s'),
                            ]
                        );
                    }
                }
            }
            $productid = $request->productid;
            $statusdeal = $request->statusdeal;
            ProductDeal::where('deal_id', $request->id)->delete();
            if (isset($productid) && ! empty($productid)) {
                foreach ($productid as $key => $productValue) {
                    // Parse product_id and variant_id from format: "product_id" or "product_id_vvariant_id"
                    $productId = $productValue;
                    $variantId = null;

                    if (strpos($productValue, '_v') !== false) {
                        $parts = explode('_v', $productValue);
                        $productId = $parts[0];
                        $variantId = $parts[1];
                    }

                    ProductDeal::insertGetId(
                        [
                            'deal_id' => $request->id,
                            'product_id' => $productId,
                            'variant_id' => $variantId,
                            'status' => (isset($statusdeal) && isset($statusdeal[$productId])) ? $statusdeal[$productId] : '0',
                            'created_at' => date('Y-m-d H:i:s'),
                        ]
                    );
                }
            }

            try {
                // Allocate holds for current config
                $newLimited = (int) ($request->limited ?? 0);
                $this->allocateDealHoldsForDeal((int) $request->id, $newLimited);
            } catch (\Exception $e) {
                Log::error('[DEAL_HOLD_SYNC] allocate failed on update', [
                    'deal_id' => (int) $request->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Sửa thành công!',
                'url' => route('deal.edit', ['id' => $request->id]),
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Sửa không thành công!']],
            ]);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start' => 'required',
            'end' => 'required',
        ], [
            'start.required' => 'Thời gian bắt đầu không được bỏ trống.',
            'end.required' => 'Thời gian kết thúc không được bỏ trống',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ]);
        }
        $id = $this->model::insertGetId(
            [
                'name' => $request->name,
                'start' => strtotime($request->start),
                'end' => strtotime($request->end),
                'status' => $request->status,
                'limited' => $request->limited,
                'user_id' => Auth::id(),
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );
        if ($id > 0) {
            // Validation: Check conflicts before saving
            $productid = $request->productid;
            $pricesale = $request->pricesale;
            $numbersale = $request->numbersale;
            $status2 = $request->status2;

            // Rule 1: Sản phẩm phụ không thể là sản phẩm chính trong cùng deal
            if (isset($pricesale) && ! empty($pricesale) && isset($productid) && ! empty($productid)) {
                $mainProductKeys = [];
                foreach ($productid as $productValue) {
                    $mainProductKeys[] = $productValue;
                }

                foreach ($pricesale as $key => $value) {
                    $saleProductKey = $key;
                    if (is_array($value)) {
                        foreach ($value as $vId => $price) {
                            $saleProductKey = $key.'_v'.$vId;
                            if (in_array($saleProductKey, $mainProductKeys)) {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => 'Sản phẩm "'.$this->getProductName($key, $vId).'" đã là sản phẩm chính, không thể thêm làm sản phẩm phụ!',
                                ]);
                            }
                        }
                    } else {
                        if (in_array($saleProductKey, $mainProductKeys)) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Sản phẩm "'.$this->getProductName($key).'" đã là sản phẩm chính, không thể thêm làm sản phẩm phụ!',
                            ]);
                        }
                    }
                }
            }

            // Rule 2: Sản phẩm chính không thể là sản phẩm chính của deal khác đang active
            if (isset($productid) && ! empty($productid)) {
                $currentTime = time();
                $activeDeals = Deal::where('status', '1')
                    ->where(function ($q) use ($currentTime) {
                        $q->where('start', '<=', $currentTime)
                            ->where('end', '>=', $currentTime);
                    })
                    ->pluck('id');

                if ($activeDeals->count() > 0) {
                    $conflictProducts = ProductDeal::whereIn('deal_id', $activeDeals)
                        ->get();

                    foreach ($productid as $productValue) {
                        $productId = $productValue;
                        $variantId = null;

                        if (strpos($productValue, '_v') !== false) {
                            $parts = explode('_v', $productValue);
                            $productId = $parts[0];
                            $variantId = $parts[1];
                        }

                        foreach ($conflictProducts as $conflictProduct) {
                            if ($conflictProduct->product_id == $productId &&
                                $conflictProduct->variant_id == $variantId) {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => 'Sản phẩm "'.$this->getProductName($productId, $variantId).'" đã là sản phẩm chính của Deal khác đang hoạt động!',
                                ]);
                            }
                        }
                    }
                }
            }

            if (isset($pricesale) && ! empty($pricesale)) {
                foreach ($pricesale as $key => $value) {
                    // Parse product_id and variant_id from key format: "product_id" or "product_id[variant_id]"
                    $productId = $key;
                    $variantId = null;

                    // Check if key is array format: product_id[variant_id]
                    if (is_array($value)) {
                        // This is nested array format
                        foreach ($value as $vId => $price) {
                            SaleDeal::insertGetId(
                                [
                                    'deal_id' => $id,
                                    'product_id' => $key,
                                    'variant_id' => $vId,
                                    'price' => ($price != '') ? str_replace(',', '', $price) : 0,
                                    'qty' => (isset($numbersale[$key][$vId]) && ! empty($numbersale[$key][$vId])) ? $numbersale[$key][$vId] : '0',
                                    'status' => (isset($status2[$key][$vId]) && isset($status2[$key][$vId])) ? $status2[$key][$vId] : '0',
                                    'created_at' => date('Y-m-d H:i:s'),
                                ]
                            );
                        }
                    } else {
                        // Old format: single product without variant
                        SaleDeal::insertGetId(
                            [
                                'deal_id' => $id,
                                'product_id' => $key,
                                'variant_id' => null,
                                'price' => ($value != '') ? str_replace(',', '', $value) : 0,
                                'qty' => (isset($numbersale) && ! empty($numbersale)) ? $numbersale[$key] : '0',
                                'status' => (isset($status2) && isset($status2[$key])) ? $status2[$key] : '0',
                                'created_at' => date('Y-m-d H:i:s'),
                            ]
                        );
                    }
                }
            }
            $productid = $request->productid;
            $statusdeal = $request->statusdeal;
            if (isset($productid) && ! empty($productid)) {
                foreach ($productid as $k => $productValue) {
                    // Parse product_id and variant_id from format: "product_id" or "product_id_vvariant_id"
                    $productId = $productValue;
                    $variantId = null;

                    if (strpos($productValue, '_v') !== false) {
                        $parts = explode('_v', $productValue);
                        $productId = $parts[0];
                        $variantId = $parts[1];
                    }

                    ProductDeal::insertGetId(
                        [
                            'deal_id' => $id,
                            'product_id' => $productId,
                            'variant_id' => $variantId,
                            'status' => (isset($statusdeal) && isset($statusdeal[$productId])) ? $statusdeal[$productId] : '0',
                            'created_at' => date('Y-m-d H:i:s'),
                        ]
                    );
                }
            }

            try {
                $limited = (int) ($request->limited ?? 0);
                $this->allocateDealHoldsForDeal((int) $id, $limited);
            } catch (\Exception $e) {
                Log::error('[DEAL_HOLD_SYNC] allocate failed on store', [
                    'deal_id' => (int) $id,
                    'error' => $e->getMessage(),
                ]);
            }

            Session::forget('ss_sale_product');
            Session::forget('ss_product_deal');

            return response()->json([
                'status' => 'success',
                'alert' => 'Tạo thành công!',
                'url' => route('deal'),
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Tạo không thành công!']],
            ]);
        }
    }

    public function delete(Request $request)
    {
        $deal = $this->model::find($request->id);
        $limited = (int) ($deal->limited ?? 0);
        try {
            $this->releaseDealHoldsForDeal((int) $request->id, $limited);
        } catch (\Exception $e) {
            Log::error('[DEAL_HOLD_SYNC] release failed on delete', [
                'deal_id' => (int) $request->id,
                'error' => $e->getMessage(),
            ]);
        }

        $data = $this->model::findOrFail($request->id)->delete();
        ProductDeal::where('deal_id', $request->id)->delete();
        SaleDeal::where('deal_id', $request->id)->delete();
        if ($request->page != '') {
            $url = route('deal').'?page='.$request->page;
        } else {
            $url = route('deal');
        }

        return response()->json([
            'status' => 'success',
            'alert' => 'Xóa thành công!',
            'url' => $url,
        ]);
    }

    public function status(Request $request)
    {
        $this->model::where('id', $request->id)->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'status' => 'success',
            'alert' => 'Đổi trạng thái thành công!',
            'url' => route('deal'),
        ]);
    }

    public function action(Request $request)
    {
        $check = $request->checklist;
        if (! isset($check) && empty($check)) {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Chưa chọn dữ liệu cần thao tác!']],
            ]);
        }
        $action = $request->action;
        if ($action == 0) {
            foreach ($check as $key => $value) {
                $this->model::where('id', $value)->update([
                    'status' => '0',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Ẩn thành công!',
                'url' => route('deal'),
            ]);
        } elseif ($action == 1) {
            foreach ($check as $key => $value) {
                $this->model::where('id', $value)->update([
                    'status' => '1',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Hiển thị thành công!',
                'url' => route('deal'),
            ]);
        } else {
            foreach ($check as $key => $value) {
                $this->model::where('id', $value)->delete();
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => route('deal'),
            ]);
        }
    }

    public function showProduct($deal_id)
    {
        $now = strtotime(date('Y-m-d H:i:s'));
        if (isset($deal_id) && $deal_id != '') {
            $sales = Deal::select('id')->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now], ['id', '!=', $deal_id]])->get();
        } else {
            $sales = Deal::select('id')->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]])->get();
        }
        $mang = $sales->pluck('id')->toArray();
        $products = ProductDeal::select('product_id')->whereIn('deal_id', $mang)->get()->pluck('product_id')->toArray();

        return $products;
    }

    public function loadProduct(Request $request)
    {
        $search = $request->search;
        $brand = $request->brand;
        $page = $request->page;
        $limit = $this->limit;
        $offset = ($page - 1) * $limit;
        $mang = $this->showProduct($request->deal_id);
        $products = Product::select('id', 'name', 'image', 'stock', 'has_variants')
            ->where([['status', '1'], ['type', 'product']])
            ->whereNotIn('id', $mang)
            ->where(function ($query) use ($search, $brand) {
                if (isset($search) && $search != '') {
                    $query->where('name', 'like', '%'.$search.'%');
                }
                if (isset($brand) && $brand != '') {
                    $query->where('brand_id', $brand);
                }
            })
            ->limit($limit * 2) // Load more to filter by stock
            ->offset($offset)
            ->get();

        // Load variants for products that have variants
        foreach ($products as $product) {
            if ($product->has_variants == 1) {
                $product->load('variants');
            }
        }

        // Filter products with stock > 0 and calculate actual stock
        $productsWithStock = $products->filter(function ($product) {
            $stock = $this->productStockValidator->getProductStock($product->id);

            if ($stock <= 0) {
                // Check if product has variants with stock > 0
                if ($product->has_variants == 1 && $product->variants) {
                    $hasStock = false;
                    foreach ($product->variants as $variant) {
                        $variantStock = $this->productStockValidator->getProductStock(
                            $product->id,
                            $variant->id
                        );
                        if ($variantStock > 0) {
                            $hasStock = true;
                            break;
                        }
                    }

                    return $hasStock;
                }

                return false;
            }

            return true;
        })->take($limit); // Take only the limit after filtering

        // Add actual stock to products and variants
        $productsWithStock = $productsWithStock->map(function ($product) {
            if ($product->has_variants == 1 && $product->variants) {
                $product->variants = $product->variants->map(function ($variant) use ($product) {
                    $variant->actual_stock = $this->productStockValidator->getProductStock(
                        $product->id,
                        $variant->id
                    );

                    return $variant;
                });
            } else {
                $product->actual_stock = $this->productStockValidator->getProductStock($product->id);
            }

            return $product;
        });

        $total = Product::select('id')
            ->where([['status', '1'], ['type', 'product']])
            ->whereNotIn('id', $mang)
            ->where(function ($query) use ($search, $brand) {
                if (isset($search) && $search != '') {
                    $query->where('name', 'like', '%'.$search.'%');
                }
                if (isset($brand) && $brand != '') {
                    $query->where('brand_id', $brand);
                }
            })
            ->get()
            ->filter(function ($product) {
                $stock = $this->productStockValidator->getProductStock($product->id);
                if ($stock <= 0) {
                    // For products with variants, we'd need to check variants
                    // For simplicity, we'll count them and filter later
                    return true; // Include in count, will be filtered in view
                }

                return true;
            })
            ->count();

        $pages = ceil($total / $limit);
        $data['pages'] = $pages;
        $data['products'] = $productsWithStock;
        $data['brands'] = Brand::select('id', 'name')->where('status', '1')->orderBy('name', 'asc')->get();
        $data['deal_id'] = $request->deal_id;
        $view = view($this->view.'::products', $data)->render();

        return response()->json([
            'page' => $page,
            'search' => $search,
            'brand' => $brand,
            'html' => $view,
        ]);
    }

    public function choseProduct(Request $request)
    {
        // if(Session::has('ss_product_sale')){
        //     $ss_product = Session::get('ss_product_sale');
        //     if(isset($request->productid) && !empty($request->productid)){
        //         foreach ($request->productid as $key => $value) {
        //             if(in_array($value, $ss_product)){
        //             }else{
        //                 array_push($ss_product, $value);
        //             }
        //         }
        //     }
        //     Session::put('ss_product_deal',$ss_product);
        // }else{
        //     Session::put('ss_product_deal',$request->productid);
        // }
        // Parse product IDs and variant IDs from request
        // Format: "product_id" or "product_id_vvariant_id"
        $productIds = [];
        $productVariantMap = [];

        foreach ($request->productid as $item) {
            if (strpos($item, '_v') !== false) {
                // Has variant
                $parts = explode('_v', $item);
                $productId = (int) $parts[0];
                $variantId = (int) $parts[1];
                $productIds[] = $productId;
                $productVariantMap[$productId][] = $variantId;
            } else {
                // No variant
                $productId = (int) $item;
                $productIds[] = $productId;
                $productVariantMap[$productId] = [];
            }
        }

        // Load products with variants
        $products = Product::where('type', 'product')
            ->whereIn('id', array_unique($productIds))
            ->with(['variants' => function ($q) {
                $q->with(['color', 'size']);
            }])
            ->get();

        // Calculate actual stock and available stock for each product/variant
        $productsWithStock = $products->map(function ($product) use ($productVariantMap) {
            if ($product->has_variants == 1 && $product->variants) {
                // Product has variants - filter only selected variants
                $selectedVariantIds = $productVariantMap[$product->id] ?? [];
                if (! empty($selectedVariantIds)) {
                    $product->variants = $product->variants->filter(function ($variant) use ($selectedVariantIds) {
                        return in_array($variant->id, $selectedVariantIds);
                    })->map(function ($variant) use ($product) {
                        $variant->actual_stock = $this->productStockValidator->getProductStock(
                            $product->id,
                            $variant->id
                        );
                        // Calculate available stock (S_phy - S_flash)
                        $variant->available_stock = $this->inventoryService->getAvailableStock(
                            $product->id,
                            $variant->id
                        );

                        return $variant;
                    });
                } else {
                    // No specific variants selected, show all
                    $product->variants = $product->variants->map(function ($variant) use ($product) {
                        $variant->actual_stock = $this->productStockValidator->getProductStock(
                            $product->id,
                            $variant->id
                        );
                        // Calculate available stock (S_phy - S_flash)
                        $variant->available_stock = $this->inventoryService->getAvailableStock(
                            $product->id,
                            $variant->id
                        );

                        return $variant;
                    });
                }
            } else {
                // Product without variants
                $product->actual_stock = $this->productStockValidator->getProductStock($product->id);
                // Calculate available stock (S_phy - S_flash)
                $product->available_stock = $this->inventoryService->getAvailableStock($product->id);
            }

            return $product;
        });

        // Store selected products in session - merge with existing
        if (Session::has('ss_product_deal')) {
            $ss_product = Session::get('ss_product_deal');
            if (! is_array($ss_product)) {
                $ss_product = [];
            }
            foreach ($request->productid as $item) {
                if (! in_array($item, $ss_product)) {
                    $ss_product[] = $item;
                }
            }
            Session::put('ss_product_deal', $ss_product);
        } else {
            Session::put('ss_product_deal', $request->productid);
        }

        // For AJAX requests, only return NEW products (for appending)
        // For full page loads, return ALL products from session
        if ($request->ajax() || $request->wantsJson()) {
            // Only return new products
            $data['products'] = $productsWithStock;

            return view($this->view.'::product_rows', $data);
        } else {
            // Load ALL products from session (for full page reload)
            $allProductIds = Session::get('ss_product_deal', []);
            $allProductVariantMap = [];

            foreach ($allProductIds as $item) {
                if (strpos($item, '_v') !== false) {
                    $parts = explode('_v', $item);
                    $productId = (int) $parts[0];
                    $variantId = (int) $parts[1];
                    $allProductVariantMap[$productId][] = $variantId;
                } else {
                    $productId = (int) $item;
                    $allProductVariantMap[$productId] = [];
                }
            }

            // Load all products from session
            $allProducts = Product::where('type', 'product')
                ->whereIn('id', array_unique(array_keys($allProductVariantMap)))
                ->with(['variants' => function ($q) {
                    $q->with(['color', 'size']);
                }])
                ->get();

            // Calculate stock for all products
            $allProductsWithStock = $allProducts->map(function ($product) use ($allProductVariantMap) {
                if ($product->has_variants == 1 && $product->variants) {
                    $selectedVariantIds = $allProductVariantMap[$product->id] ?? [];
                    if (! empty($selectedVariantIds)) {
                        $product->variants = $product->variants->filter(function ($variant) use ($selectedVariantIds) {
                            return in_array($variant->id, $selectedVariantIds);
                        })->map(function ($variant) use ($product) {
                            $variant->actual_stock = $this->productStockValidator->getProductStock(
                                $product->id,
                                $variant->id
                            );
                            $variant->available_stock = $this->inventoryService->getAvailableStock(
                                $product->id,
                                $variant->id
                            );

                            return $variant;
                        });
                    } else {
                        $product->variants = $product->variants->map(function ($variant) use ($product) {
                            $variant->actual_stock = $this->productStockValidator->getProductStock(
                                $product->id,
                                $variant->id
                            );
                            $variant->available_stock = $this->inventoryService->getAvailableStock(
                                $product->id,
                                $variant->id
                            );

                            return $variant;
                        });
                    }
                } else {
                    $product->actual_stock = $this->productStockValidator->getProductStock($product->id);
                    $product->available_stock = $this->inventoryService->getAvailableStock($product->id);
                }

                return $product;
            });

            $data['products'] = $allProductsWithStock;

            return view($this->view.'::loadproduct', $data);
        }
    }

    public function delProduct(Request $request)
    {
        if (Session::has('ss_product_deal')) {
            $ss_product = Session::get('ss_product_deal');
            $mang = $request->mang;
            if (isset($mang) && ! empty($mang)) {
                foreach ($mang as $key => $value) {
                    $k = array_search($value, $ss_product);
                    unset($ss_product[$k]);
                }
            }
            Session::put('ss_product_deal', $ss_product);
        }
        print_r(Session::get('ss_product_deal'));
    }

    public function loadProduct2(Request $request)
    {
        $search = $request->search;
        $brand = $request->brand;
        $page = $request->page;
        $limit = $this->limit;
        $offset = ($page - 1) * $limit;
        $products = Product::select('id', 'name', 'image', 'stock', 'has_variants')
            ->where([['status', '1'], ['type', 'product']])
            ->where(function ($query) use ($search, $brand) {
                if (isset($search) && $search != '') {
                    $query->where('name', 'like', '%'.$search.'%');
                }
                if (isset($brand) && $brand != '') {
                    $query->where('brand_id', $brand);
                }
            })
            ->limit($limit * 2) // Load more to filter by stock
            ->offset($offset)
            ->get();

        // Load variants for products that have variants
        foreach ($products as $product) {
            if ($product->has_variants == 1) {
                $product->load('variants');
            }
        }

        // Filter products with stock > 0 and calculate actual stock
        $productsWithStock = $products->filter(function ($product) {
            $stock = $this->productStockValidator->getProductStock($product->id);

            if ($stock <= 0) {
                // Check if product has variants with stock > 0
                if ($product->has_variants == 1 && $product->variants) {
                    $hasStock = false;
                    foreach ($product->variants as $variant) {
                        $variantStock = $this->productStockValidator->getProductStock(
                            $product->id,
                            $variant->id
                        );
                        if ($variantStock > 0) {
                            $hasStock = true;
                            break;
                        }
                    }

                    return $hasStock;
                }

                return false;
            }

            return true;
        })->take($limit); // Take only the limit after filtering

        // Add actual stock to products and variants
        $productsWithStock = $productsWithStock->map(function ($product) {
            if ($product->has_variants == 1 && $product->variants) {
                $product->variants = $product->variants->map(function ($variant) use ($product) {
                    $variant->actual_stock = $this->productStockValidator->getProductStock(
                        $product->id,
                        $variant->id
                    );

                    return $variant;
                });
            } else {
                $product->actual_stock = $this->productStockValidator->getProductStock($product->id);
            }

            return $product;
        });

        $total = Product::select('id')
            ->where([['status', '1'], ['type', 'product']])
            ->where(function ($query) use ($search, $brand) {
                if (isset($search) && $search != '') {
                    $query->where('name', 'like', '%'.$search.'%');
                }
                if (isset($brand) && $brand != '') {
                    $query->where('brand_id', $brand);
                }
            })
            ->get()
            ->count();

        $pages = ceil($total / $limit);
        $data['pages'] = $pages;
        $data['products'] = $productsWithStock;
        $data['deal_id'] = $request->deal_id;
        $data['brands'] = Brand::select('id', 'name')->where('status', '1')->orderBy('name', 'asc')->get();
        $view = view($this->view.'::products2', $data)->render();

        return response()->json([
            'page' => $page,
            'search' => $search,
            'brand' => $brand,
            'html' => $view,
        ]);
    }

    public function choseProduct2(Request $request)
    {
        // Parse product IDs and variant IDs from request
        // Format: "product_id" or "product_id_vvariant_id"
        $productIds = [];
        $productVariantMap = [];

        foreach ($request->productid as $item) {
            if (strpos($item, '_v') !== false) {
                // Has variant
                $parts = explode('_v', $item);
                $productId = (int) $parts[0];
                $variantId = (int) $parts[1];
                $productIds[] = $productId;
                $productVariantMap[$productId][] = $variantId;
            } else {
                // No variant
                $productId = (int) $item;
                $productIds[] = $productId;
                $productVariantMap[$productId] = [];
            }
        }

        // Load products with variants
        $products = Product::where('type', 'product')
            ->whereIn('id', array_unique($productIds))
            ->with(['variants' => function ($q) {
                $q->with(['color', 'size']);
            }])
            ->get();

        // Calculate actual stock and available stock for each product/variant
        $productsWithStock = $products->map(function ($product) use ($productVariantMap) {
            if ($product->has_variants == 1 && $product->variants) {
                // Product has variants - filter only selected variants
                $selectedVariantIds = $productVariantMap[$product->id] ?? [];
                if (! empty($selectedVariantIds)) {
                    $product->variants = $product->variants->filter(function ($variant) use ($selectedVariantIds) {
                        return in_array($variant->id, $selectedVariantIds);
                    })->map(function ($variant) use ($product) {
                        $variant->actual_stock = $this->productStockValidator->getProductStock(
                            $product->id,
                            $variant->id
                        );
                        // Calculate available stock (S_phy - S_flash)
                        $variant->available_stock = $this->inventoryService->getAvailableStock(
                            $product->id,
                            $variant->id
                        );

                        return $variant;
                    });
                } else {
                    // No specific variants selected, show all
                    $product->variants = $product->variants->map(function ($variant) use ($product) {
                        $variant->actual_stock = $this->productStockValidator->getProductStock(
                            $product->id,
                            $variant->id
                        );
                        // Calculate available stock (S_phy - S_flash)
                        $variant->available_stock = $this->inventoryService->getAvailableStock(
                            $product->id,
                            $variant->id
                        );

                        return $variant;
                    });
                }
            } else {
                // Product without variants
                $product->actual_stock = $this->productStockValidator->getProductStock($product->id);
                // Calculate available stock (S_phy - S_flash)
                $product->available_stock = $this->inventoryService->getAvailableStock($product->id);
            }

            return $product;
        });

        // Store selected products in session - merge with existing
        if (Session::has('ss_sale_product')) {
            $ss_product = Session::get('ss_sale_product');
            if (! is_array($ss_product)) {
                $ss_product = [];
            }
            foreach ($request->productid as $item) {
                if (! in_array($item, $ss_product)) {
                    $ss_product[] = $item;
                }
            }
            Session::put('ss_sale_product', $ss_product);
        } else {
            Session::put('ss_sale_product', $request->productid);
        }

        // For AJAX requests, only return NEW products (for appending)
        // For full page loads, return ALL products from session
        if ($request->ajax() || $request->wantsJson()) {
            // Only return new products
            $data['products'] = $productsWithStock;

            return view($this->view.'::sale_product_rows', $data);
        } else {
            // Load ALL products from session (for full page reload)
            $allProductIds = Session::get('ss_sale_product', []);
            $allProductVariantMap = [];

            foreach ($allProductIds as $item) {
                if (strpos($item, '_v') !== false) {
                    $parts = explode('_v', $item);
                    $productId = (int) $parts[0];
                    $variantId = (int) $parts[1];
                    $allProductVariantMap[$productId][] = $variantId;
                } else {
                    $productId = (int) $item;
                    $allProductVariantMap[$productId] = [];
                }
            }

            // Load all products from session
            $allProducts = Product::where('type', 'product')
                ->whereIn('id', array_unique(array_keys($allProductVariantMap)))
                ->with(['variants' => function ($q) {
                    $q->with(['color', 'size']);
                }])
                ->get();

            // Calculate stock for all products
            $allProductsWithStock = $allProducts->map(function ($product) use ($allProductVariantMap) {
                if ($product->has_variants == 1 && $product->variants) {
                    $selectedVariantIds = $allProductVariantMap[$product->id] ?? [];
                    if (! empty($selectedVariantIds)) {
                        $product->variants = $product->variants->filter(function ($variant) use ($selectedVariantIds) {
                            return in_array($variant->id, $selectedVariantIds);
                        })->map(function ($variant) use ($product) {
                            $variant->actual_stock = $this->productStockValidator->getProductStock(
                                $product->id,
                                $variant->id
                            );
                            $variant->available_stock = $this->inventoryService->getAvailableStock(
                                $product->id,
                                $variant->id
                            );

                            return $variant;
                        });
                    } else {
                        $product->variants = $product->variants->map(function ($variant) use ($product) {
                            $variant->actual_stock = $this->productStockValidator->getProductStock(
                                $product->id,
                                $variant->id
                            );
                            $variant->available_stock = $this->inventoryService->getAvailableStock(
                                $product->id,
                                $variant->id
                            );

                            return $variant;
                        });
                    }
                } else {
                    $product->actual_stock = $this->productStockValidator->getProductStock($product->id);
                    $product->available_stock = $this->inventoryService->getAvailableStock($product->id);
                }

                return $product;
            });

            $data['products'] = $allProductsWithStock;

            return view($this->view.'::load_product', $data);
        }
    }

    public function delProduct2(Request $request)
    {
        if (Session::has('ss_sale_product')) {
            $ss_product = Session::get('ss_sale_product');
            $mang = $request->mang;
            if (isset($mang) && ! empty($mang)) {
                foreach ($mang as $key => $value) {
                    $k = array_search($value, $ss_product);
                    unset($ss_product[$k]);
                }
            }
            Session::put('ss_sale_product', $ss_product);
        }
    }

    public function addSession(Request $request)
    {
        if (Session::has('ss_product_deal')) {
            $ss_product = Session::get('ss_product_deal');
            $mang = $request->mang;
            if (isset($mang) && ! empty($mang)) {
                foreach ($mang as $key => $value) {
                    if (in_array($value, $ss_product)) {
                    } else {
                        array_push($ss_product, $value);
                    }
                }
            }
            Session::put('ss_product_deal', $ss_product);
        } else {
            Session::put('ss_product_deal', $request->mang);
        }
    }

    public function delSession(Request $request)
    {
        if (Session::has('ss_product_deal')) {
            $ss_product = Session::get('ss_product_deal');
            $mang = $request->mang;
            if (isset($mang) && ! empty($mang)) {
                foreach ($mang as $key => $value) {
                    $k = array_search($value, $ss_product);
                    unset($ss_product[$k]);
                }
            }
            Session::put('ss_product_deal', $ss_product);
        }
    }

    public function addSession2(Request $request)
    {
        if (Session::has('ss_sale_product')) {
            $ss_product = Session::get('ss_sale_product');
            $mang = $request->mang;
            if (isset($mang) && ! empty($mang)) {
                foreach ($mang as $key => $value) {
                    if (in_array($value, $ss_product)) {
                    } else {
                        array_push($ss_product, $value);
                    }
                }
            }
            Session::put('ss_sale_product', $ss_product);
        } else {
            Session::put('ss_sale_product', $request->mang);
        }
    }

    public function delSession2(Request $request)
    {
        if (Session::has('ss_sale_product')) {
            $ss_product = Session::get('ss_sale_product');
            $mang = $request->mang;
            if (isset($mang) && ! empty($mang)) {
                foreach ($mang as $key => $value) {
                    $k = array_search($value, $ss_product);
                    unset($ss_product[$k]);
                }
            }
            Session::put('ss_sale_product', $ss_product);
        }
    }

    // Ajax Search - Use same logic as Flash Sale to get product info
    public function searchProduct(Request $request)
    {
        $keyword = $request->get('keyword');
        $type = $request->get('type', 'main'); // 'main' or 'sale'
        $dealId = $request->get('deal_id', null); // Current deal ID (for edit)

        // Get excluded product IDs based on type
        $excludedProductVariantPairs = [];

        if ($type === 'sale') {
            // Rule 1: Sản phẩm phụ không thể là sản phẩm chính trong cùng deal
            if ($dealId) {
                $mainProducts = ProductDeal::where('deal_id', $dealId)
                    ->get();
                foreach ($mainProducts as $mainProduct) {
                    $key = $mainProduct->product_id.($mainProduct->variant_id ? '_v'.$mainProduct->variant_id : '');
                    $excludedProductVariantPairs[] = $key;
                }
            }
        } elseif ($type === 'main') {
            // Rule 2: Sản phẩm chính không thể là sản phẩm chính của deal khác đang active
            $currentTime = time();
            $activeDeals = Deal::where('status', '1')
                ->where(function ($q) use ($currentTime) {
                    $q->where('start', '<=', $currentTime)
                        ->where('end', '>=', $currentTime);
                })
                ->when($dealId, function ($q) use ($dealId) {
                    // Exclude current deal when editing
                    $q->where('id', '!=', $dealId);
                })
                ->pluck('id');

            if ($activeDeals->count() > 0) {
                $conflictProducts = ProductDeal::whereIn('deal_id', $activeDeals)
                    ->get();
                foreach ($conflictProducts as $conflictProduct) {
                    $key = $conflictProduct->product_id.($conflictProduct->variant_id ? '_v'.$conflictProduct->variant_id : '');
                    $excludedProductVariantPairs[] = $key;
                }
            }
        }

        $products = Product::select('id', 'name', 'image', 'stock', 'has_variants')
            ->where([['status', '1'], ['type', 'product']])
            ->where('name', 'like', '%'.$keyword.'%')
            ->with(['variants' => function ($q) {
                $q->select('id', 'product_id', 'option1_value', 'price', 'stock', 'sku');
            }])
            ->orderBy('id', 'desc')
            ->paginate(50);

        $html = '';
        foreach ($products as $product) {
            // Get actual stock from warehouse system (same as API)
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
                    if (! $hasStock) {
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
            $price = $variant ? $variant->price : 0;
            $image = getImage($product->image);

            if ($product->has_variants == 1 && $product->variants) {
                // Product with variants - show each variant with stock > 0
                foreach ($product->variants as $v) {
                    $variantStock = $this->productStockValidator->getProductStock($product->id, $v->id);
                    if ($variantStock <= 0) {
                        continue;
                    }

                    // Calculate available stock (S_phy - S_flash)
                    $availableStock = $this->inventoryService->getAvailableStock($product->id, $v->id);

                    $html .= '<tr>';
                    $html .= '<td width="5%" style="text-align: center;">';
                    $html .= '<input style="margin: 0px;display: inline-block;" type="checkbox" name="productid[]" class="checkbox wgr-checkbox" value="'.$product->id.'_v'.$v->id.'" data-product-id="'.$product->id.'" data-variant-id="'.$v->id.'" data-original-price="'.$v->price.'" data-stock="'.$variantStock.'" data-available-stock="'.$availableStock.'">';
                    $html .= '</td>';
                    $html .= '<td width="35%">';
                    $html .= '<img src="'.$image.'" style="width:50px;height: 50px;float: left;margin-right: 5px;">';
                    $html .= '<p><strong>'.$product->name.'</strong></p>';
                    $html .= '<small class="text-muted">Phân loại: '.($v->option1_value ?? 'N/A').'</small>';
                    $html .= '</td>';
                    $html .= '<td width="12%">'.number_format($v->price).'đ</td>';
                    $html .= '<td width="12%">-</td>';
                    $html .= '<td width="12%" style="text-align: center;"><strong>'.number_format($variantStock).'</strong></td>';
                    $html .= '<td width="12%" style="text-align: center;"><strong class="text-info">'.number_format($availableStock).'</strong></td>';
                    $html .= '</tr>';
                }
            } else {
                // Product without variants
                // Calculate available stock (S_phy - S_flash)
                $availableStock = $this->inventoryService->getAvailableStock($product->id);

                $html .= '<tr>';
                $html .= '<td width="5%" style="text-align: center;">';
                $html .= '<input style="margin: 0px;display: inline-block;" type="checkbox" name="productid[]" class="checkbox wgr-checkbox" value="'.$product->id.'" data-product-id="'.$product->id.'" data-variant-id="" data-original-price="'.$price.'" data-stock="'.$stock.'" data-available-stock="'.$availableStock.'">';
                $html .= '</td>';
                $html .= '<td width="35%">';
                $html .= '<img src="'.$image.'" style="width:50px;height: 50px;float: left;margin-right: 5px;">';
                $html .= '<p>'.$product->name.'</p>';
                $html .= '</td>';
                $html .= '<td width="12%">'.($price > 0 ? number_format($price).'đ' : '-').'</td>';
                $html .= '<td width="12%">-</td>';
                $html .= '<td width="12%" style="text-align: center;"><strong>'.number_format($stock).'</strong></td>';
                $html .= '<td width="12%" style="text-align: center;"><strong class="text-info">'.number_format($availableStock).'</strong></td>';
                $html .= '</tr>';
            }
        }

        // If no products found, show message
        if (empty($html)) {
            $html = '<tr><td colspan="6" class="text-center">Không tìm thấy sản phẩm có tồn kho</td></tr>';
        }

        return response()->json(['html' => $html]);
    }

    /**
     * Helper method to get product name for error messages.
     */
    private function getProductName($productId, $variantId = null)
    {
        $product = Product::find($productId);
        if (! $product) {
            return "ID: {$productId}";
        }

        $name = $product->name;
        if ($variantId) {
            $variant = \App\Modules\Product\Models\Variant::find($variantId);
            if ($variant && $variant->option1_value) {
                $name .= ' - '.$variant->option1_value;
            }
        }

        return $name;
    }
}
