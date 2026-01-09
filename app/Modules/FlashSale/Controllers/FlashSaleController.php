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
        $data['productsales'] = ProductSale::where('flashsale_id',$detail->id)->get();
        // Optimized: Do not load all products
        return view($this->view.'::edit',$data);
    }
    
    public function update(Request $request)
    {
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
            $checklist = $request->checklist; // Selected product IDs

            // FIX: Delete products that were removed from the UI
            if(isset($checklist)){
                 ProductSale::where('flashsale_id', $request->id)->whereNotIn('product_id', $checklist)->delete();
            } else {
                 // If empty checklist, remove all
                 ProductSale::where('flashsale_id', $request->id)->delete();
            }

            if(isset($pricesale) && !empty($pricesale)){
                foreach ($pricesale as $key => $value) {
                    $product = ProductSale::where([['flashsale_id',$request->id],['product_id',$key]])->first();
                    if(isset($product) && !empty($product)){
                        ProductSale::where('id',$product->id)->update([
                            'price_sale' => ($value != "")?str_replace(',','', $value):0,
                            'number' => (isset($numbersale) && !empty($numbersale))?$numbersale[$key]:'0',
                        ]);
                    }else{
                        ProductSale::insertGetId(
                            [
                                'flashsale_id' => $request->id,
                                'product_id' => $key,
                                'price_sale' => ($value != "")?str_replace(',','', $value):0,
                                'number' => (isset($numbersale) && !empty($numbersale))?$numbersale[$key]:'0',
                                'created_at' => date('Y-m-d H:i:s')
                            ]
                        );
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
    }

    public function store(Request $request)
    {   
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
        $id = $this->model::insertGetId(
            [
                'start' => strtotime($request->start),
                'end' => strtotime($request->end),
                'status' => $request->status,
                'user_id'=> Auth::id(),
                'created_at' => date('Y-m-d H:i:s')
            ]
        );
        if($id > 0){
            $pricesale =  $request->pricesale;
            $numbersale = $request->numbersale;
            if(isset($pricesale) && !empty($pricesale)){
                foreach ($pricesale as $key => $value) {
                    ProductSale::insertGetId(
                        [
                            'flashsale_id' => $id,
                            'product_id' => $key,
                            'price_sale' => ($value != "")?str_replace(',','', $value):0,
                            'number' => (isset($numbersale) && !empty($numbersale))?$numbersale[$key]:'0',
                            'created_at' => date('Y-m-d H:i:s')
                        ]
                    );
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
        // This is loadProduct
        $data['products'] =  Product::select('id','name','image')->where('type','product')->whereIn('id',$request->productid)->get();
        // Return only rows view (I will create it next)
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
