<?php

namespace App\Modules\FlashSale\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\FlashSale\Models\FlashSale;
use App\Modules\Product\Models\Product;
use App\Modules\FlashSale\Models\ProductSale;
use Validator;
use Illuminate\Support\Facades\Auth;
use Session;

class FlashSaleController extends Controller
{
    private $model;
    private $controller = 'flashsale';
    private $view = 'FlashSale';
    
    public function __construct(FlashSale $model){
        $this->model = $model;
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
        $data['products'] = Product::whereIn('id', $productIds)
            ->with(['variants' => function($q) {
                $q->with(['color', 'size']);
            }])
            ->get();
        
        return view($this->view.'::edit',$data);
    }
    
    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start' => 'required',
                'end' => 'required',
            ],[
                'start.required' => 'Thời gian bắt đầu không được bỏ trống.',
                'end.required' => 'Thời gian kết thúc không được bỏ trống',
            ]);
            if($validator->fails()) {
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
                            $numberValue = isset($numbersale[$productId][$variantId]) ? $numbersale[$productId][$variantId] : '0';
                            
                            $variantIdInt = (int)$variantId;
                            $productIdInt = (int)$productId;
                            
                            $productSale = ProductSale::where([
                                ['flashsale_id', (int)$request->id],
                                ['product_id', $productIdInt],
                                ['variant_id', $variantIdInt]
                            ])->first();
                            
                            if($productSale){
                                ProductSale::where('id', $productSale->id)->update([
                                    'price_sale' => ($priceValue != "") ? str_replace(',','', $priceValue) : 0,
                                    'number' => (int)$numberValue,
                                ]);
                            } else {
                                $productSale = new ProductSale();
                                $productSale->flashsale_id = (int)$request->id;
                                $productSale->product_id = $productIdInt;
                                $productSale->variant_id = $variantIdInt;
                                $productSale->price_sale = ($priceValue != "") ? str_replace(',','', $priceValue) : 0;
                                $productSale->number = (int)$numberValue;
                                $productSale->buy = 0;
                                $productSale->save();
                            }
                        }
                    } else {
                        // Product without variants (old logic)
                        $numberValue = isset($numbersale[$productId]) ? $numbersale[$productId] : '0';
                        
                        $productIdInt = (int)$productId;
                        
                        $productSale = ProductSale::where([
                            ['flashsale_id', (int)$request->id],
                            ['product_id', $productIdInt],
                        ])->whereNull('variant_id')->first();
                        
                        if($productSale){
                            ProductSale::where('id', $productSale->id)->update([
                                'price_sale' => ($variants != "") ? str_replace(',','', $variants) : 0,
                                'number' => (int)$numberValue,
                            ]);
                        } else {
                            $productSale = new ProductSale();
                            $productSale->flashsale_id = (int)$request->id;
                            $productSale->product_id = $productIdInt;
                            $productSale->variant_id = null;
                            $productSale->price_sale = ($variants != "") ? str_replace(',','', $variants) : 0;
                            $productSale->number = (int)$numberValue;
                            $productSale->buy = 0;
                            $productSale->save();
                        }
                    }
                }
            }
                return response()->json([
                    'status' => 'success',
                    'alert' => 'Sửa thành công!',
                    'url' => route('flashsale')
                ]);
            }else{
                return response()->json([
                    'status' => 'error',
                    'errors' => array('alert' => array('0' => 'Sửa không thành công!'))
                ]);
            }
        } catch (\Exception $e) {
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
        try {
            $validator = Validator::make($request->all(), [
                'start' => 'required',
                'end' => 'required',
            ],[
                'start.required' => 'Thời gian bắt đầu không được bỏ trống.',
                'end.required' => 'Thời gian kết thúc không được bỏ trống',
            ]);
            if($validator->fails()) {
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
            $pricesale =  $request->pricesale;
            $numbersale = $request->numbersale;
            if(isset($pricesale) && !empty($pricesale)){
                foreach ($pricesale as $productId => $variants) {
                    if(is_array($variants)){
                        // Product has variants
                        foreach($variants as $variantId => $priceValue){
                            $numberValue = isset($numbersale[$productId][$variantId]) ? $numbersale[$productId][$variantId] : '0';
                            
                            $productSale = new ProductSale();
                            $productSale->flashsale_id = $id;
                            $productSale->product_id = (int)$productId;
                            $productSale->variant_id = (int)$variantId;
                            $productSale->price_sale = ($priceValue != "") ? str_replace(',','', $priceValue) : 0;
                            $productSale->number = (int)$numberValue;
                            $productSale->buy = 0;
                            $productSale->save();
                        }
                    } else {
                        // Product without variants (old logic)
                        $numberValue = isset($numbersale[$productId]) ? $numbersale[$productId] : '0';
                        
                        $productSale = new ProductSale();
                        $productSale->flashsale_id = $id;
                        $productSale->product_id = (int)$productId;
                        $productSale->variant_id = null;
                        $productSale->price_sale = ($variants != "") ? str_replace(',','', $variants) : 0;
                        $productSale->number = (int)$numberValue;
                        $productSale->buy = 0;
                        $productSale->save();
                    }
                }
            }
                return response()->json([
                    'status' => 'success',
                    'alert' => 'Tạo thành công!',
                    'url' => route('flashsale')
                ]);
            }else{
                return response()->json([
                    'status' => 'error',
                    'errors' => array('alert' => array('0' => 'Tạo không thành công!'))
                ]);
            }
        } catch (\Exception $e) {
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
        // Load products with variants
        $data['products'] = Product::where('type','product')
            ->whereIn('id', $request->productid)
            ->with(['variants' => function($q) {
                $q->with(['color', 'size']);
            }])
            ->get();
        // Return only rows view
        return view($this->view.'::product_rows',$data);
    }

    // Ajax Search
    public function searchProduct(Request $request)
    {
        $keyword = $request->get('keyword');
        $products = Product::select('id', 'name', 'image', 'stock')
            ->where([['status', '1'], ['type', 'product']])
            ->where('name', 'like', '%' . $keyword . '%')
            ->orderBy('id', 'desc')
            ->paginate(50); 

        $html = '';
        foreach($products as $product) {
            $variant = $product->variant($product->id);
            $price = $variant ? number_format($variant->price) . 'đ' : '-';
            $image = getImage($product->image);
            
            $html .= '<tr>';
            $html .= '<td width="5%" style="text-align: center;">';
            $html .= '<input style="margin: 0px;display: inline-block;" type="checkbox" name="productid[]" class="checkbox wgr-checkbox" value="'.$product->id.'">';
            $html .= '</td>';
            $html .= '<td width="55%">';
            $html .= '<img src="'.$image.'" style="width:50px;height: 50px;float: left;margin-right: 5px;">';
            $html .= '<p>'.$product->name.'</p>';
            $html .= '</td>';
            $html .= '<td width="20%">'.$price.'</td>';
            $html .= '<td width="20%">-</td>'; 
            $html .= '</tr>';
        }
        
        return response()->json(['html' => $html]);
    }
}
