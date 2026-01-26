<?php

declare(strict_types=1);
namespace App\Modules\Product\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Services\Product\ProductServiceInterface;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Modules\Brand\Models\Brand;
use App\Modules\Color\Models\Color;
use App\Modules\Size\Models\Size;
use App\Modules\Origin\Models\Origin;
use App\Modules\Dictionary\Models\IngredientPaulas;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use Validator;
use Illuminate\Support\Facades\Auth;
use App\Modules\Order\Models\OrderDetail;

class ProductController extends Controller
{
    private $model;
    private $module = 'Product';
    private ProductServiceInterface $productService;

    public function __construct(Product $model, ProductServiceInterface $productService)
    {
        $this->model = $model;
        $this->productService = $productService;
    }

    /**
     * Display a listing of products
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        active('product', 'list');
        
        // Prepare filters
        $filters = [];
        if ($request->get('status') != "") {
            $filters['status'] = $request->get('status');
        }
        if ($request->get('cat_id') != "") {
            $filters['cat_id'] = $request->get('cat_id');
        }
        if ($request->get('keyword') != "") {
            $filters['keyword'] = $request->get('keyword');
        }
        
        // Get products using service
        $data['products'] = $this->productService->getProducts($filters, 10);
        
        // Get categories for filter
        $data['categories'] = $this->model::where([
            ['type', ProductType::TAXONOMY->value],
            ['status', ProductStatus::ACTIVE->value]
        ])->orderBy('sort', 'asc')->get();
        
        return view($this->module . '::index', $data);
    }

    /**
     * Show the form for creating a new product
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        active('product', 'list');
        
        $data['categories'] = $this->model::where([
            ['type', ProductType::TAXONOMY->value],
            ['status', ProductStatus::ACTIVE->value],
            ['cat_id', '0']
        ])->orderBy('sort', 'asc')->get();
        
        $data['brands'] = Brand::where('status', ProductStatus::ACTIVE->value)
            ->orderBy('name', 'asc')->get();
        
        $data['origins'] = Origin::where('status', ProductStatus::ACTIVE->value)
            ->orderBy('sort', 'asc')->get();
        
        // Use IngredientPaulas dictionary as ingredient source
        $data['ingredients'] = IngredientPaulas::where('status', '1')
            ->orderBy('name', 'asc')->get();
        
        return view($this->module . '::create', $data);
    }

    /**
     * Show the form for editing a product
     * 
     * @param int $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit($id)
    {
        active('product', 'list');
        
        try {
            // Get product with relations using service
            $detail = $this->productService->getProductWithRelations($id);
        } catch (\Exception $e) {
            return redirect()->route('product')
                ->with('error', 'Sản phẩm không tồn tại');
        }
        
        $data['categories'] = $this->model::where([
            ['type', ProductType::TAXONOMY->value],
            ['status', ProductStatus::ACTIVE->value],
            ['cat_id', '0']
        ])->orderBy('sort', 'asc')->get();
        
        $data['detail'] = $detail;
        $data['brands'] = Brand::where('status', ProductStatus::ACTIVE->value)
            ->orderBy('name', 'asc')->get();
        
        $data['origins'] = Origin::where('status', ProductStatus::ACTIVE->value)
            ->orderBy('sort', 'asc')->get();
        
        // Use IngredientPaulas dictionary as ingredient source
        $data['ingredients'] = IngredientPaulas::where('status', '1')
            ->orderBy('name', 'asc')->get();
        
        // Safely decode gallery JSON
        $galleryJson = $detail->gallery ?? '[]';
        $decodedGallery = json_decode($galleryJson, true);
        $data['gallerys'] = is_array($decodedGallery) ? $decodedGallery : [];
        
        // Safely decode cat_id JSON
        $catIdJson = $detail->cat_id ?? '[]';
        $decodedCatId = json_decode($catIdJson, true);
        $data['dcat'] = is_array($decodedCatId) ? $decodedCatId : [];
        
        return view($this->module . '::edit', $data);
    }

    /**
     * Store a newly created product
     * 
     * @param StoreProductRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreProductRequest $request)
    {
        try {
            // Check SKU uniqueness if provided
            if ($request->sku) {
                $check = Variant::where('sku', $request->sku)->exists();
                if ($check) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'SKU đã tồn tại'
                    ]);
                }
            }
            
            // Create product using service
            $product = $this->productService->createProduct($request->validated());
            
            return response()->json([
                'status' => 'success',
                'alert' => 'Thêm thành công!',
                'url' => route('product')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing product
     * 
     * @param UpdateProductRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateProductRequest $request)
    {
        try {
            // Ensure gallery array is clean (avoid empty strings)
            $validated = $request->validated();
            if (isset($validated['imageOther']) && is_array($validated['imageOther'])) {
                $validated['imageOther'] = array_values(array_filter($validated['imageOther'], function ($v) {
                    return is_string($v) && trim($v) !== '';
                }));
            }
            if (isset($validated['imageOtherRemoved']) && is_array($validated['imageOtherRemoved'])) {
                $validated['imageOtherRemoved'] = array_values(array_filter($validated['imageOtherRemoved'], function ($v) {
                    return is_string($v) && trim($v) !== '';
                }));
            }

            // Update product using service
            $product = $this->productService->updateProduct(
                $request->id,
                $validated
            );
            
            return response()->json([
                'status' => 'success',
                'alert' => 'Sửa thành công!',
                'url' => '/admin/product/edit/' . $request->id . '?t=' . time()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified product
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        try {
            $this->productService->deleteProduct($request->id);
            
            $url = route('product');
            if ($request->page != "") {
                $url .= '?page=' . $request->page;
            }
            
            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => $url
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update product status
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function status(Request $request)
    {
        $this->model::where('id', $request->id)->update([
            'status' => $request->status
        ]);
        
        return response()->json([
            'status' => 'success',
            'alert' => 'Đổi trạng thái thành công!',
            'url' => route('product')
        ]);
    }

    /**
     * Bulk actions on products
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function action(Request $request)
    {
        $check = $request->checklist;
        if (empty($check)) {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Chưa chọn dữ liệu cần thao tác!']]
            ]);
        }
        
        $action = $request->action;
        if ($action == 0) {
            $this->model::whereIn('id', $check)->update([
                'status' => ProductStatus::INACTIVE->value
            ]);
            return response()->json([
                'status' => 'success',
                'alert' => 'Ẩn thành công!',
                'url' => route('product')
            ]);
        } elseif ($action == 1) {
            $this->model::whereIn('id', $check)->update([
                'status' => ProductStatus::ACTIVE->value
            ]);
            return response()->json([
                'status' => 'success',
                'alert' => 'Hiển thị thành công!',
                'url' => route('product')
            ]);
        } else {
            // Delete multiple products
            foreach ($check as $id) {
                try {
                    $this->productService->deleteProduct($id);
                } catch (\Exception $e) {
                    // Log error but continue with other deletions
                    \Illuminate\Support\Facades\Log::error("Failed to delete product {$id}: " . $e->getMessage());
                }
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => route('product')
            ]);
        }
    }

    /**
     * Update sort order for multiple products
     * 
     * @param Request $req
     * @return void
     */
    public function postSort(Request $req)
    {
        $sort = $req->sort;
        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $key => $val) {
                $this->model::where('id', $key)->update([
                    'sort' => $val
                ]);
            }
        }
    }

    /**
     * Update sort order for a single product
     * 
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function sort(Request $req)
    {
        $this->model::where('id', $req->id)->update([
            'sort' => $req->sort
        ]);
        
        return response()->json([
            'status' => 'success',
            'alert' => 'Đổi thứ tự thành công!',
            'url' => route('product')
        ]);
    }

    /**
     * Show variant edit page
     * 
     * @param int $id Product ID
     * @param int $code Variant ID
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function variant($id, $code)
    {
        active('product', 'list');
        
        $product = $this->model::find($id);
        $detail = Variant::find($code);
        
        if (!$product || !$detail) {
            return redirect()->route('product');
        }
        
        $data['detail'] = $detail;
        $data['product'] = $product;
        $data['variants'] = $product->variants;
        $data['colors'] = Color::where('status', ProductStatus::ACTIVE->value)->get();
        $data['sizes'] = Size::where('status', ProductStatus::ACTIVE->value)
            ->orderBy('sort', 'asc')->get();
        
        return view($this->module . '::variant', $data);
    }

    /**
     * Update variant
     * 
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function editvariant(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'sku' => 'required|unique:variants,sku,' . $req->id,
        ], [
            'sku.required' => 'Bạn chưa nhập Sku',
            'sku.unique' => 'Sku đã tồn tại',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }
        
        $update = Variant::where('id', $req->id)->update([
            'sku' => $req->sku,
            'size_id' => $req->size_id,
            'color_id' => $req->color_id,
            'image' => $req->image,
            'weight' => $req->weight,
            'price' => str_replace(',', '', $req->price),
            'sale' => str_replace(',', '', $req->sale),
            'user_id' => Auth::id(),
        ]);
        
        if ($update > 0) {
            return response()->json([
                'status' => 'success',
                'alert' => 'Cập nhật biến thể thành công!',
                'url' => route('product.variant', ['id' => $req->product_id, 'code' => $req->id])
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Cập nhật biến thể không thành công!']]
            ]);
        }
    }

    /**
     * Show variant creation page
     * 
     * @param int $id Product ID
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function variantnew($id)
    {
        active('product', 'list');
        
        $product = $this->model::find($id);
        if (!$product) {
            return redirect()->route('product');
        }
        
        $data['product'] = $product;
        $data['variants'] = $product->variants;
        $data['first'] = Variant::where('product_id', $id)
            ->orderBy('id', 'asc')->first();
        $data['colors'] = Color::where('status', ProductStatus::ACTIVE->value)->get();
        $data['sizes'] = Size::where('status', ProductStatus::ACTIVE->value)
            ->orderBy('sort', 'asc')->get();
        
        return view($this->module . '::variantnew', $data);
    }

    /**
     * Create new variant
     * 
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function createvariant(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'sku' => 'required|unique:variants,sku',
        ], [
            'sku.required' => 'Bạn chưa nhập Sku',
            'sku.unique' => 'Sku đã tồn tại',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }
        
        $id = Variant::insertGetId([
            'sku' => $req->sku,
            'product_id' => $req->product_id,
            'image' => $req->image,
            'size_id' => $req->size_id,
            'color_id' => $req->color_id,
            'weight' => $req->weight,
            'price' => str_replace(',', '', $req->price),
            'sale' => str_replace(',', '', $req->sale),
            'user_id' => Auth::id(),
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($id > 0) {
            return response()->json([
                'status' => 'success',
                'alert' => 'Tạo biến thể thành công!',
                'url' => route('product.variant', ['id' => $req->product_id, 'code' => $id])
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Tạo biến thể không thành công!']]
            ]);
        }
    }

    /**
     * Delete variant
     * 
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function delvariant(Request $req)
    {
        $order = OrderDetail::select('id')->where('product_id', $req->id)->exists();
        if ($order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sản phẩm đã có đơn hàng không thể xóa!'
            ]);
        } else {
            Variant::findOrFail($req->id)->delete();
            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
            ]);
        }
    }

    /**
     * Process ingredients - convert plain text to linked ingredients
     * 
     * This method is kept for backward compatibility but logic is now in ProductService
     * 
     * @param string|null $content
     * @return string
     */
    public function processIngredients($content)
    {
        if (empty($content)) {
            return $content;
        }

        // Strip tags to work with plain text
        $cleanContent = strip_tags($content);
        
        // Split by comma (standard ingredient separator)
        $parts = preg_split('/,\s*/', $cleanContent);
        
        // Filter empty
        $parts = array_filter($parts, function($value) { 
            return trim($value) !== ''; 
        });
        
        if (empty($parts)) {
            return $content;
        }

        // Get unique names to query
        $names = array_map('trim', $parts);
        $names = array_unique($names);

        // Query DB for these names (Case-insensitive)
        $ingredients = Ingredient::whereIn('name', $names)
            ->where('status', ProductStatus::ACTIVE->value)
            ->get();
        
        // Map for lookup
        $ingMap = [];
        foreach ($ingredients as $ing) {
            $ingMap[strtolower($ing->name)] = $ing;
        }

        // Rebuild content
        $processedParts = [];
        foreach ($parts as $part) {
            $trimPart = trim($part);
            $lowerPart = strtolower($trimPart);
            
            if (isset($ingMap[$lowerPart])) {
                $ing = $ingMap[$lowerPart];
                // Link using official name from DB
                $processedParts[] = '<a href="javascript:;" class="item_ingredient" data-id="'.$ing->slug.'">'.$ing->name.'</a>';
            } else {
                $processedParts[] = $trimPart;
            }
        }

        return implode(', ', $processedParts);
    }
}
