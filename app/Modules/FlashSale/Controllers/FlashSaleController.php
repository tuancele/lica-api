<?php

namespace App\Modules\FlashSale\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\FlashSale\Models\FlashSale;
use App\Modules\Product\Models\Product;
use App\Modules\FlashSale\Models\ProductSale;
use App\Services\Promotion\ProductStockValidatorInterface;
use App\Services\Inventory\InventoryServiceInterface;
use Validator;
use Illuminate\Support\Facades\Auth;
use Session;
use Illuminate\Support\Facades\DB;

class FlashSaleController extends Controller
{
    private $model;
    private $controller = 'flashsale';
    private $view = 'FlashSale';
    protected ProductStockValidatorInterface $productStockValidator;
    protected InventoryServiceInterface $inventoryService;
    
    public function __construct(
        FlashSale $model, 
        ProductStockValidatorInterface $productStockValidator,
        InventoryServiceInterface $inventoryService
    ){
        $this->model = $model;
        $this->productStockValidator = $productStockValidator;
        $this->inventoryService = $inventoryService;
    }
    
    public function index(Request $request)
    {
        active('flashsale','flashsale');
        $data['list'] = $this->model::where(function ($query) use ($request) {
            if($request->get('status') != "") {
	            $query->where('status', $request->get('status'));
            }
            if($request->get('keyword') != "") {
	            $query->where('name','like','%'.$request->get('keyword').'%');
	        }
        })->latest()->paginate(10)->appends($request->query());
        return view($this->view.'::index',$data);
    }
    
    public function create(){
        active('flashsale','flashsale');
        // Optimized: Do not load all products
        return view($this->view.'::create');
    }
    
    public function edit($id){
        active('flashsale','flashsale');
        $detail = $this->model::find($id);
        if(!isset($detail) && empty($detail)){
            return redirect()->route('flashsale');
        }
        $data['detail'] = $detail;
        $data['productsales'] = ProductSale::where('flashsale_id',$detail->id)
            ->with(['product.variants', 'variant'])
            ->get();
        
        // Load products with variants for display
        $productIds = $data['productsales']->pluck('product_id')->unique();
        $products = Product::whereIn('id', $productIds)
            ->with(['variants' => function($q) {
                $q->with(['color', 'size']);
            }])
            ->get();
        
        // Calculate actual stock and available stock for each product/variant
        $productsWithStock = $products->map(function($product) {
            if ($product->has_variants == 1 && $product->variants) {
                // Product has variants - calculate stock for each variant
                $product->variants = $product->variants->map(function($variant) use ($product) {
                    $warehouseStock = app(\App\Services\Warehouse\WarehouseServiceInterface::class)->getVariantStock($variant->id);
                    $variant->actual_stock = $warehouseStock['physical_stock'] ?? 0;
                    // Calculate available stock (S_phy - S_flash)
                    $variant->available_stock = $warehouseStock['available_stock'] ?? 0;
                    return $variant;
                });
            } else {
                // Product without variants
                $variant = $product->variant($product->id);
                $stockId = $variant ? $variant->id : $product->id;
                $warehouseStock = app(\App\Services\Warehouse\WarehouseServiceInterface::class)->getVariantStock($stockId);
                $product->actual_stock = $warehouseStock['physical_stock'] ?? 0;
                // Calculate available stock (S_phy - S_flash)
                $product->available_stock = $warehouseStock['available_stock'] ?? 0;
            }
            return $product;
        });
        
        $data['products'] = $productsWithStock;
        
        return view($this->view.'::edit',$data);
    }
    
    public function update(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'start' => 'required',
                'end' => 'required',
            ],[
                'start.required' => 'Thời gian bắt đầu không được bỏ trống.',
                'end.required' => 'Thời gian kết thúc không được bỏ trống',
            ]);
            if($validator->fails()) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ]);
            }
            $up = $this->model::where('id',$request->id)->update(array(
                'start' => strtotime($request->start),
                'end' => strtotime($request->end),
                'status' => $request->status,
                'user_id'=> Auth::id()
            ));
            
            if($up > 0){
            $pricesale =  $request->pricesale;
            $numbersale = $request->numbersale;
            $checklist = $request->checklist; // Selected product/variant IDs

            // Get existing keys to keep
            $existingKeys = [];
            if(isset($pricesale) && is_array($pricesale)){
                foreach($pricesale as $productId => $variants){
                    if(is_array($variants)){
                        // Product has variants
                        foreach($variants as $variantId => $price){
                            $existingKeys[] = $productId . '_' . $variantId;
                        }
                    } else {
                        // Product without variants
                        $existingKeys[] = $productId . '_null';
                    }
                }
            }

            // Delete products/variants not in checklist or pricesale
            // Build list of product_id + variant_id combinations to keep
            $keepCombinations = [];
            if(isset($pricesale) && is_array($pricesale)){
                foreach($pricesale as $productId => $variants){
                    if(is_array($variants)){
                        // Product has variants
                        foreach($variants as $variantId => $price){
                            $keepCombinations[] = ['product_id' => $productId, 'variant_id' => $variantId];
                        }
                    } else {
                        // Product without variants
                        $keepCombinations[] = ['product_id' => $productId, 'variant_id' => null];
                    }
                }
            }
            
            // Delete products not in keep list
            if(!empty($keepCombinations)){
                // Get all existing ProductSales for this flash sale
                $existingProductSales = ProductSale::where('flashsale_id', $request->id)->get();
                
                // Delete those not in keep list
                foreach($existingProductSales as $existingSale){
                    $found = false;
                    foreach($keepCombinations as $keep){
                        if((int)$existingSale->product_id == (int)$keep['product_id']){
                            $keepVariantId = $keep['variant_id'] !== null ? (int)$keep['variant_id'] : null;
                            $existingVariantId = $existingSale->variant_id !== null ? (int)$existingSale->variant_id : null;
                            
                            if($keepVariantId === $existingVariantId){
                                $found = true;
                                break;
                            }
                        }
                    }
                    if(!$found){
                        $existingSale->delete();
                    }
                }
            } else {
                // If no pricesale data, check checklist
                if(isset($checklist) && !empty($checklist)){
                    // Keep only items in checklist
                    $keepCombinations = [];
                    foreach($checklist as $item){
                        if(strpos($item, '_v') !== false){
                            // Has variant: product_id_variant_id
                            list($productId, $variantId) = explode('_v', $item);
                            $keepCombinations[] = ['product_id' => $productId, 'variant_id' => $variantId];
                        } else {
                            // No variant: product_id
                            $keepCombinations[] = ['product_id' => $item, 'variant_id' => null];
                        }
                    }
                    
                    if(!empty($keepCombinations)){
                        // Get all existing ProductSales for this flash sale
                        $existingProductSales = ProductSale::where('flashsale_id', $request->id)->get();
                        
                        // Delete those not in keep list
                        foreach($existingProductSales as $existingSale){
                            $found = false;
                            foreach($keepCombinations as $keep){
                                if((int)$existingSale->product_id == (int)$keep['product_id']){
                                    $keepVariantId = $keep['variant_id'] !== null ? (int)$keep['variant_id'] : null;
                                    $existingVariantId = $existingSale->variant_id !== null ? (int)$existingSale->variant_id : null;
                                    
                                    if($keepVariantId === $existingVariantId){
                                        $found = true;
                                        break;
                                    }
                                }
                            }
                            if(!$found){
                                $existingSale->delete();
                            }
                        }
                    }
                } else {
                    // If empty checklist and no pricesale, remove all
                    ProductSale::where('flashsale_id', $request->id)->delete();
                }
            }

            // Process pricesale data
            if(isset($pricesale) && !empty($pricesale)){
                foreach ($pricesale as $productId => $variants) {
                    if(is_array($variants)){
                        // Product has variants
                        foreach($variants as $variantId => $priceValue){
                            $numberValue = isset($numbersale[$productId][$variantId]) ? (int)$numbersale[$productId][$variantId] : 0;
                            $priceValue = ($priceValue != "") ? str_replace(',','', $priceValue) : 0;
                            
                            $variantIdInt = (int)$variantId;
                            $productIdInt = (int)$productId;
                            
                            // Validate: total_stock >= flash_stock_limit (number) - only for new or updated number
                            $stockValidation = $this->inventoryService->validateFlashSaleStock(
                                $productIdInt,
                                $variantIdInt,
                                $numberValue
                            );
                            
                            if (!$stockValidation['valid']) {
                                $product = Product::find($productIdInt);
                                $productName = $product ? $product->name : "ID {$productIdInt}";
                                DB::rollBack();
                                return response()->json([
                                    'status' => 'error',
                                    'errors' => [
                                        'alert' => ["Sản phẩm \"{$productName}\" (Variant ID {$variantIdInt}): " . $stockValidation['message']]
                                    ]
                                ], 422);
                            }
                            
                            // Validate price: price_sale <= original_price
                            $variant = \App\Modules\Product\Models\Variant::find($variantIdInt);
                            if ($variant && $priceValue > $variant->price) {
                                $product = Product::find($productIdInt);
                                $productName = $product ? $product->name : "ID {$productIdInt}";
                                DB::rollBack();
                                return response()->json([
                                    'status' => 'error',
                                    'errors' => [
                                        'alert' => ["Sản phẩm \"{$productName}\" (Variant ID {$variantIdInt}): Giá khuyến mại ({$priceValue}đ) không thể lớn hơn giá gốc ({$variant->price}đ)"]
                                    ]
                                ], 422);
                            }
                            
                            $productSale = ProductSale::where([
                                ['flashsale_id', (int)$request->id],
                                ['product_id', $productIdInt],
                                ['variant_id', $variantIdInt]
                            ])->first();
                            
                            if($productSale){
                                ProductSale::where('id', $productSale->id)->update([
                                    'price_sale' => (float)$priceValue,
                                    'number' => $numberValue,
                                ]);
                            } else {
                                $productSale = new ProductSale();
                                $productSale->flashsale_id = (int)$request->id;
                                $productSale->product_id = $productIdInt;
                                $productSale->variant_id = $variantIdInt;
                                $productSale->price_sale = (float)$priceValue;
                                $productSale->number = $numberValue;
                                $productSale->buy = 0;
                                $productSale->user_id = Auth::id();
                                $productSale->save();
                            }
                        }
                    } else {
                        // Product without variants
                        $numberValue = isset($numbersale[$productId]) ? (int)$numbersale[$productId] : 0;
                        $priceValue = ($variants != "") ? str_replace(',','', $variants) : 0;
                        
                        $productIdInt = (int)$productId;
                        
                        // Validate: total_stock >= flash_stock_limit (number)
                        $stockValidation = $this->inventoryService->validateFlashSaleStock(
                            $productIdInt,
                            null,
                            $numberValue
                        );
                        
                        if (!$stockValidation['valid']) {
                            $product = Product::find($productIdInt);
                            $productName = $product ? $product->name : "ID {$productIdInt}";
                            DB::rollBack();
                            return response()->json([
                                'status' => 'error',
                                'errors' => [
                                    'alert' => ["Sản phẩm \"{$productName}\": " . $stockValidation['message']]
                                ]
                            ], 422);
                        }
                        
                        // Validate price: price_sale <= original_price
                        $product = Product::find($productIdInt);
                        $variant = $product ? $product->variant($productIdInt) : null;
                        if ($variant && $priceValue > $variant->price) {
                            $productName = $product ? $product->name : "ID {$productIdInt}";
                            DB::rollBack();
                            return response()->json([
                                'status' => 'error',
                                'errors' => [
                                    'alert' => ["Sản phẩm \"{$productName}\": Giá khuyến mại ({$priceValue}đ) không thể lớn hơn giá gốc ({$variant->price}đ)"]
                                ]
                            ], 422);
                        }
                        
                        $productSale = ProductSale::where([
                            ['flashsale_id', (int)$request->id],
                            ['product_id', $productIdInt],
                        ])->whereNull('variant_id')->first();
                        
                        if($productSale){
                            ProductSale::where('id', $productSale->id)->update([
                                'price_sale' => (float)$priceValue,
                                'number' => $numberValue,
                            ]);
                        } else {
                            $productSale = new ProductSale();
                            $productSale->flashsale_id = (int)$request->id;
                            $productSale->product_id = $productIdInt;
                            $productSale->variant_id = null;
                            $productSale->price_sale = (float)$priceValue;
                            $productSale->number = $numberValue;
                            $productSale->buy = 0;
                            $productSale->user_id = Auth::id();
                            $productSale->save();
                        }
                    }
                }
            }
                
                DB::commit();
                return response()->json([
                    'status' => 'success',
                    'alert' => 'Sửa thành công!',
                    'url' => route('flashsale')
                ]);
            }else{
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'errors' => array('alert' => array('0' => 'Sửa không thành công!'))
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Flash Sale Update Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'status' => 'error',
                'errors' => array('alert' => array('0' => 'Có lỗi xảy ra: ' . $e->getMessage()))
            ], 500);
        }
    }

    public function store(Request $request)
    {   
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'start' => 'required',
                'end' => 'required',
            ],[
                'start.required' => 'Thời gian bắt đầu không được bỏ trống.',
                'end.required' => 'Thời gian kết thúc không được bỏ trống',
            ]);
            if($validator->fails()) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ]);
            }
            
            $flashSale = new FlashSale();
            $flashSale->start = strtotime($request->start);
            $flashSale->end = strtotime($request->end);
            $flashSale->status = $request->status;
            $flashSale->user_id = Auth::id();
            $flashSale->save();
            $id = $flashSale->id;
            
            if($id > 0){
                $pricesale = $request->pricesale;
                $numbersale = $request->numbersale;
                
                if(isset($pricesale) && !empty($pricesale)){
                    foreach ($pricesale as $productId => $variants) {
                        if(is_array($variants)){
                            // Product has variants
                            foreach($variants as $variantId => $priceValue){
                                $numberValue = isset($numbersale[$productId][$variantId]) ? (int)$numbersale[$productId][$variantId] : 0;
                                $priceValue = ($priceValue != "") ? str_replace(',','', $priceValue) : 0;
                                
                                // Validate: total_stock >= flash_stock_limit (number)
                                $stockValidation = $this->inventoryService->validateFlashSaleStock(
                                    (int)$productId,
                                    (int)$variantId,
                                    $numberValue
                                );
                                
                                if (!$stockValidation['valid']) {
                                    $product = Product::find($productId);
                                    $productName = $product ? $product->name : "ID {$productId}";
                                    DB::rollBack();
                                    return response()->json([
                                        'status' => 'error',
                                        'errors' => [
                                            'alert' => ["Sản phẩm \"{$productName}\" (Variant ID {$variantId}): " . $stockValidation['message']]
                                        ]
                                    ], 422);
                                }
                                
                                // Validate price: price_sale <= original_price
                                $variant = \App\Modules\Product\Models\Variant::find($variantId);
                                if ($variant && $priceValue > $variant->price) {
                                    $product = Product::find($productId);
                                    $productName = $product ? $product->name : "ID {$productId}";
                                    DB::rollBack();
                                    return response()->json([
                                        'status' => 'error',
                                        'errors' => [
                                            'alert' => ["Sản phẩm \"{$productName}\" (Variant ID {$variantId}): Giá khuyến mại ({$priceValue}đ) không thể lớn hơn giá gốc ({$variant->price}đ)"]
                                        ]
                                    ], 422);
                                }
                                
                                $productSale = new ProductSale();
                                $productSale->flashsale_id = $id;
                                $productSale->product_id = (int)$productId;
                                $productSale->variant_id = (int)$variantId;
                                $productSale->price_sale = (float)$priceValue;
                                $productSale->number = $numberValue;
                                $productSale->buy = 0;
                                $productSale->user_id = Auth::id();
                                $productSale->save();
                            }
                        } else {
                            // Product without variants
                            $numberValue = isset($numbersale[$productId]) ? (int)$numbersale[$productId] : 0;
                            $priceValue = ($variants != "") ? str_replace(',','', $variants) : 0;
                            
                            // Validate: total_stock >= flash_stock_limit (number)
                            $stockValidation = $this->inventoryService->validateFlashSaleStock(
                                (int)$productId,
                                null,
                                $numberValue
                            );
                            
                            if (!$stockValidation['valid']) {
                                $product = Product::find($productId);
                                $productName = $product ? $product->name : "ID {$productId}";
                                DB::rollBack();
                                return response()->json([
                                    'status' => 'error',
                                    'errors' => [
                                        'alert' => ["Sản phẩm \"{$productName}\": " . $stockValidation['message']]
                                    ]
                                ], 422);
                            }
                            
                            // Validate price: price_sale <= original_price
                            $product = Product::find($productId);
                            $variant = $product ? $product->variant($productId) : null;
                            if ($variant && $priceValue > $variant->price) {
                                $productName = $product ? $product->name : "ID {$productId}";
                                DB::rollBack();
                                return response()->json([
                                    'status' => 'error',
                                    'errors' => [
                                        'alert' => ["Sản phẩm \"{$productName}\": Giá khuyến mại ({$priceValue}đ) không thể lớn hơn giá gốc ({$variant->price}đ)"]
                                    ]
                                ], 422);
                            }
                            
                            $productSale = new ProductSale();
                            $productSale->flashsale_id = $id;
                            $productSale->product_id = (int)$productId;
                            $productSale->variant_id = null;
                            $productSale->price_sale = (float)$priceValue;
                            $productSale->number = $numberValue;
                            $productSale->buy = 0;
                            $productSale->user_id = Auth::id();
                            $productSale->save();
                        }
                    }
                }
                
                DB::commit();
                return response()->json([
                    'status' => 'success',
                    'alert' => 'Tạo thành công!',
                    'url' => route('flashsale')
                ]);
            }else{
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'errors' => array('alert' => array('0' => 'Tạo không thành công!'))
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Flash Sale Store Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'status' => 'error',
                'errors' => array('alert' => array('0' => 'Có lỗi xảy ra: ' . $e->getMessage()))
            ], 500);
        }
    }
    public function delete(Request $request)
    {
        $data = $this->model::findOrFail($request->id)->delete();
        ProductSale::where('flashsale_id',$request->id)->delete();
        if($request->page !=""){
            $url = route('flashsale').'?page='.$request->page;
        }else{
            $url = route('flashsale');
        }
        return response()->json([
            'status' => 'success',
            'alert' => 'Xóa thành công!',
            'url' => $url
        ]);
    }
    public function status(Request $request){
        $this->model::where('id',$request->id)->update(array(
            'status' => $request->status
        ));
        return response()->json([
            'status' => 'success',
            'alert' => 'Đổi trạng thái thành công!',
            'url' => route('flashsale')
        ]);
    }
    public function action(Request $request){
        $check = $request->checklist;
        if(!isset($check) && empty($check)){
            return response()->json([
                'status' => 'error',
                'errors' => array('alert' => array('0' => 'Chưa chọn dữ liệu cần thao tác!'))
            ]);
        }
        $action = $request->action;
        if($action == 0){
            foreach($check as $key => $value){
                $this->model::where('id',$value)->update(array(
                    'status' => '0'
                ));
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Ẩn thành công!',
                'url' => route('flashsale')
            ]);
        }elseif($action == 1){
            foreach($check as $key => $value){
                $this->model::where('id',$value)->update(array(
                    'status' => '1'
                ));
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Hiển thị thành công!',
                'url' => route('flashsale')
            ]);
        }else{
            foreach($check as $key => $value){
                $this->model::where('id',$value)->delete();
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => route('flashsale')
            ]);
        }
    }

    public function choseProduct(Request $request){
        // Parse product IDs and variant IDs from request
        // Format: "product_id" or "product_id_vvariant_id"
        $productIds = [];
        $productVariantMap = [];
        
        foreach($request->productid as $item) {
            if(strpos($item, '_v') !== false) {
                // Has variant
                $parts = explode('_v', $item);
                $productId = (int)$parts[0];
                $variantId = (int)$parts[1];
                $productIds[] = $productId;
                $productVariantMap[$productId][] = $variantId;
            } else {
                // No variant
                $productId = (int)$item;
                $productIds[] = $productId;
                $productVariantMap[$productId] = [];
            }
        }
        
        // Load products with variants
        $products = Product::where('type','product')
            ->whereIn('id', array_unique($productIds))
            ->with(['variants' => function($q) {
                $q->with(['color', 'size']);
            }])
            ->get();
        
        // Calculate actual stock and available stock for each product/variant
        $productsWithStock = $products->map(function($product) use ($productVariantMap) {
            if ($product->has_variants == 1 && $product->variants) {
                // Product has variants - filter only selected variants
                $selectedVariantIds = $productVariantMap[$product->id] ?? [];
                if (!empty($selectedVariantIds)) {
                    $product->variants = $product->variants->filter(function($variant) use ($selectedVariantIds) {
                        return in_array($variant->id, $selectedVariantIds);
                    })->map(function($variant) use ($product) {
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
                    $product->variants = $product->variants->map(function($variant) use ($product) {
                        $warehouseStock = app(\App\Services\Warehouse\WarehouseServiceInterface::class)->getVariantStock($variant->id);
                        $variant->actual_stock = $warehouseStock['physical_stock'] ?? 0;
                        // Calculate available stock (S_phy - S_flash)
                        $variant->available_stock = $warehouseStock['available_stock'] ?? 0;
                        return $variant;
                    });
                }
            } else {
                // Product without variants
                $variant = $product->variant($product->id);
                $stockId = $variant ? $variant->id : $product->id;
                $warehouseStock = app(\App\Services\Warehouse\WarehouseServiceInterface::class)->getVariantStock($stockId);
                $product->actual_stock = $warehouseStock['physical_stock'] ?? 0;
                // Calculate available stock (S_phy - S_flash)
                $product->available_stock = $warehouseStock['available_stock'] ?? 0;
            }
            return $product;
        });
        
        $data['products'] = $productsWithStock;
        // Return only rows view
        return view($this->view.'::product_rows',$data);
    }

    // Ajax Search - Use same logic as API to get product info
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
            ->paginate(50); 

        $html = '';
        foreach($products as $product) {
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
            $price = $variant ? $variant->price : 0;
            $image = getImage($product->image);
            
            if ($product->has_variants == 1 && $product->variants) {
                // Product with variants - show each variant with stock > 0
                foreach($product->variants as $v) {
                    $variantStock = $this->productStockValidator->getProductStock($product->id, $v->id);
                    if ($variantStock <= 0) {
                        continue;
                    }
                    
                    // Calculate available stock (S_phy - S_flash)
                    $warehouseStock = app(\App\Services\Warehouse\WarehouseServiceInterface::class)->getVariantStock($v->id);
                    $availableStock = $warehouseStock['available_stock'] ?? 0;
                    $actual_stock = $warehouseStock['physical_stock'] ?? 0;
                    
                    $html .= '<tr>';
                    $html .= '<td width="5%" style="text-align: center;">';
                    $html .= '<input style="margin: 0px;display: inline-block;" type="checkbox" name="productid[]" class="checkbox wgr-checkbox" value="'.$product->id.'_v'.$v->id.'" data-product-id="'.$product->id.'" data-variant-id="'.$v->id.'" data-original-price="'.$v->price.'" data-stock="'.$actual_stock.'" data-available-stock="'.$availableStock.'">';
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
                $variant = $product->variant($product->id);
                $stockId = $variant ? $variant->id : $product->id;
                $warehouseStock = app(\App\Services\Warehouse\WarehouseServiceInterface::class)->getVariantStock($stockId);
                $availableStock = $warehouseStock['available_stock'] ?? 0;
                $actual_stock = $warehouseStock['physical_stock'] ?? 0;
                
                $html .= '<tr>';
                $html .= '<td width="5%" style="text-align: center;">';
                $html .= '<input style="margin: 0px;display: inline-block;" type="checkbox" name="productid[]" class="checkbox wgr-checkbox" value="'.$product->id.'" data-product-id="'.$product->id.'" data-variant-id="" data-original-price="'.$price.'" data-stock="'.$actual_stock.'" data-available-stock="'.$availableStock.'">';
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
            $html = '<tr><td colspan="5" class="text-center">Không tìm thấy sản phẩm có tồn kho</td></tr>';
        }
        
        return response()->json(['html' => $html]);
    }
}
