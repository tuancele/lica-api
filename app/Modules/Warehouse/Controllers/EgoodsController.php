<?php

namespace App\Modules\Warehouse\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use Validator;
use Illuminate\Support\Facades\Auth;
use App\Modules\Color\Models\Color;
use App\Modules\Size\Models\Size;
use App\Modules\Warehouse\Models\ProductWarehouse;
use App\Modules\Warehouse\Models\Warehouse;

class EgoodsController extends Controller
{
	public function index(Request $request)
    {
        active('warehouse','exportgoods');
        $data['list'] = Warehouse::where('type','export')->where(function ($query) use ($request) {
            if($request->get('keyword') != "") {
	            $query->where('code','like','%'.$request->get('keyword').'%')->orWhere('subject','like','%'.$request->get('keyword').'%');
	        }
        })->orderBy('created_at','desc')->paginate(10);
        return view('Warehouse::export.index',$data);
    }
    public function create(){
    	active('warehouse','exportgoods');
    	$data['products'] = Variant::select('id','sku','color_id','size_id','product_id')->latest()->get();
        return view('Warehouse::export.create',$data);
    }
    public function store(Request $request)
    {   
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:warehouse,code'
        ],[
            'code.required' => 'Bạn chưa nhập mã đơn hàng.',
            'code.unique' => 'Mã đơn hàng đã tồn tại',
        ]);
        if($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }
        $id = Warehouse::insertGetId(
            [
                'code' => $request->code,
                'subject' => $request->subject,
                'content' => $request->content,
                'type' => 'export',
                'created_at' => date('Y-m-d H:i:s'),
                'type' => 'export',
                'user_id'=> Auth::id(),
            ]
        );
        if($id > 0){
            $product = $request->product_id;
            $price = $request->price;
            $qty = $request->qty;
            if(isset($product) && !empty($product)){
                foreach ($product as $key => $value) {
                    if(isset($price) && isset($qty)){
                        $total = countProduct($value,'import');
                        if($qty[$key] <= $total){
                            ProductWarehouse::insertGetId(
                                [
                                    'variant_id' => $value,
                                    'price' => $price[$key],
                                    'qty' => $qty[$key],
                                    'type' => 'export',
                                    'warehouse_id' => $id,
                                    'created_at' => date('Y-m-d H:i:s'),
                                ]
                            );
                        }
                    }
                }
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Thêm thành công!',
                'url' => '/admin/export-goods'
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'errors' => array('alert' => array('0' => 'Thêm không thành công!'))
            ]);
        }
    } 
    public function getPrice(Request $req){
        $product = Variant::select('price','sale')->where('id',$req->id)->first();
        if(isset($product) && !empty($product)){
            if($product->sale != 0){
                return $product->sale;
            }else{
                return $product->price;
            }
        }
    }
    public function getTotal(Request $req){
        $import = countProduct($req->productid,'import');
        $export = countProduct($req->productid,'export');
        $total = $import - $export;
        if($total > 0){
            return $total;
        }else{
            return 0;
        }
    }
    public function getSize($id){
        $variant = Variant::find($id);
    	if(isset($variant) && !empty($variant)){
            echo '<option value="'.$variant->size_id.'" selected>'.$variant->size->name.''.$variant->size->unit.'</option>';
        }
    }

    public function getColor($id){
        $variant = Variant::find($id);
        if(isset($variant) && !empty($variant)){
            echo '<option value="'.$variant->color_id.'" selected>'.$variant->color->name.'</option>';
        }
    }

    public function loadAdd(){
        $data['products'] = Variant::select('id','sku','color_id','size_id','product_id')->latest()->get();
        return view('Warehouse::export.loadAdd',$data);
    }
    public function checkTotal(Request $request){
        $import = countProduct($request->productid,'import');
        $export = countProduct($request->productid,'export');
        $total = $import - $export;
        if($request->qty <= $total){
            return response()->json(true);
        }else{
            return response()->json(false);
        }
    }
    public function show(Request $request){
        $detail = Warehouse::find($request->id);
        $data['detail'] = $detail;
        $data['products'] = ProductWarehouse::where([['type','export'],['warehouse_id',$request->id]])->get();
        return view('Warehouse::export.show',$data);
    }
    public function print($id){
        $detail = Warehouse::find($id);
        $data['detail'] = $detail;
        $data['products'] = ProductWarehouse::where([['type','export'],['warehouse_id',$id]])->get();
        return view('Warehouse::export.print',$data);
    }
    public function edit($id){
        active('warehouse','exportgoods');
        $detail = Warehouse::find($id);
        if(!isset($detail) && empty($detail)){
            return redirect('admin/export-goods');
        }
        $data['detail'] = $detail;
        $data['products'] = Variant::select('id','sku','color_id','size_id','product_id')->latest()->get();
       	$data['list'] = ProductWarehouse::where([['type','export'],['warehouse_id',$id]])->orderBy('created_at','desc')->get();
        return view('Warehouse::export.edit',$data);
    }
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:warehouse,code,'.$request->id,
        ],[
            'code.required' => 'Bạn chưa nhập mã đơn hàng.',
            'code.unique' => 'Mã đơn hàng đã tồn tại',
        ]);
        if($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }
        $update = Warehouse::where('id',$request->id)->update(array(
            'code' => $request->code,
            'subject' => $request->subject,
            'content' => $request->content,
            'created_at' => date('Y-m-d H:i:s'),
            'user_id'=> Auth::id(),
        ));
        if($update > 0){
            ProductWarehouse::where('warehouse_id',$request->id)->delete();
            $product = $request->product_id;
            $price = $request->price;
            $qty = $request->qty;
            if(isset($product) && !empty($product)){
                foreach ($product as $key => $value) {
                    if(isset($price) && isset($qty)){
                        $total = countProduct($value,'import');
                        if($qty[$key] <= $total){
                            ProductWarehouse::insertGetId(
                                [
                                    'variant_id' => $value,
                                    'price' => $price[$key],
                                    'qty' => $qty[$key],
                                    'type' => 'export',
                                    'warehouse_id' => $request->id,
                                    'created_at' => date('Y-m-d H:i:s'),
                                ]
                            );
                        }
                    }
                }
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Sửa thành công!',
                'url' => '/admin/export-goods'
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'errors' => array('alert' => array('0' => 'Sửa không thành công!'))
            ]);
        }
        
    }
    public function delete(Request $request)
    {
        $data = Warehouse::findOrFail($request->id)->delete();
        if($request->page !=""){
            $url = '/admin/export-goods?page='.$request->page;
        }else{
            $url = '/admin/export-goods';
        }
        return response()->json([
            'status' => 'success',
            'alert' => 'Xóa thành công!',
            'url' => $url
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
        foreach($check as $key => $value){
            Warehouse::where('id',$value)->delete();
        }
        return response()->json([
            'status' => 'success',
            'alert' => 'Xóa thành công!',
            'url' => '/admin/export-goods'
        ]);
    }
    public function product(Request $request){
        active('warehouse','exportgoods');
        $data['list'] = ProductWarehouse::join('posts', 'posts.id', '=', 'product_warehouse.product_id')->select('product_warehouse.*','posts.name','posts.sku')->where('product_warehouse.type','export')->where(function ($query) use ($request) {
            if($request->get('keyword') != "") {
                $query->where('name','like','%'.$request->get('keyword').'%')->orWhere('sku','like','%'.$request->get('keyword').'%');
            }
        })->orderBy('product_warehouse.created_at','desc')->paginate(10);
        return view('admin.export_goods.product.index',$data);
    }
    public function deleteProduct(Request $request){
        $data = ProductWarehouse::findOrFail($request->id)->delete();
        if($request->page !=""){
            $url = '/admin/export-goods/product?page='.$request->page;
        }else{
            $url = '/admin/export-goods/product';
        }
        return response()->json([
            'status' => 'success',
            'alert' => 'Xóa thành công!',
            'url' => $url
        ]);
    }
    public function actionProduct(Request $request){
        $check = $request->checklist;
        if(!isset($check) && empty($check)){
            return response()->json([
                'status' => 'error',
                'errors' => array('alert' => array('0' => 'Chưa chọn dữ liệu cần thao tác!'))
            ]);
        }
        foreach($check as $key => $value){
            ProductWarehouse::where('id',$value)->delete();
        }
        return response()->json([
            'status' => 'success',
            'alert' => 'Xóa thành công!',
            'url' => '/admin/export-goods/product'
        ]);
    }
    public function exportProduct(Request $request){

    }
}