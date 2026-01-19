<?php

namespace App\Modules\Warehouse\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use Validator;
use Illuminate\Support\Facades\Auth;
use App\Modules\Warehouse\Models\ProductWarehouse;
use App\Modules\Warehouse\Models\Warehouse;
class IgoodsController extends Controller
{
	public function index(Request $request)
    {
        active('warehouse','importgoods');
        $data['list'] = Warehouse::where(function ($query) use ($request) {
            if($request->get('keyword') != "") {
	            $query->where('code','like','%'.$request->get('keyword').'%')->orWhere('subject','like','%'.$request->get('keyword').'%');
	        }
        })->orderBy('created_at','desc')->paginate(10);
        return view('Warehouse::import.index',$data);
    }
    public function create(){
    	active('warehouse','importgoods');
    	// Don't load all products to avoid overload - use AJAX search instead
    	$data['products'] = collect([]);
        return view('Warehouse::import.create',$data);
    }
    
    public function searchProducts(Request $request){
        $search = $request->get('q', '');
        $products = Product::select('id','name','slug')
            ->where('type','product')
            ->where('status', 1);
        
        if($search && strlen($search) >= 2) {
            $products->where('name', 'like', '%'.$search.'%');
        } else {
            // If search is too short, return empty
            return response()->json(['results' => []]);
        }
        
        $products = $products->orderBy('name','asc')
            ->limit(50)
            ->get();
        
        $results = [];
        foreach($products as $product) {
            $results[] = [
                'id' => $product->id,
                'text' => $product->name
            ];
        }
        
        return response()->json(['results' => $results]);
    }
    public function store(Request $request)
    {   
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:warehouse,code',
            'variant_id.*' => 'required|exists:variants,id'
        ],[
            'code.required' => 'Bạn chưa nhập mã đơn hàng.',
            'code.unique' => 'Mã đơn hàng đã tồn tại',
            'variant_id.*.required' => 'Vui lòng chọn phân loại cho tất cả sản phẩm.',
            'variant_id.*.exists' => 'Phân loại không hợp lệ.',
        ]);
        if($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }
        // Combine VAT invoice and content
        $content = $request->content ?? '';
        if($request->vat_invoice) {
            $content = ($content ? $content . "\n" : '') . 'Số hóa đơn VAT: ' . $request->vat_invoice;
        }
        
        $id = Warehouse::insertGetId(
            [
                'code' => $request->code,
                'subject' => $request->subject,
                'content' => $content,
                'type' => 'import',
                'created_at' => date('Y-m-d H:i:s'),
                'user_id'=> Auth::id(),
            ]
        );
        if($id > 0){
            $variants = $request->variant_id;
            $price = $request->price;
            $qty = $request->qty;
            if(isset($variants) && !empty($variants)){
                foreach ($variants as $key => $variantId) {
                    if($variantId && $variantId != ''){
                        ProductWarehouse::insertGetId(
                            [
                                'variant_id' => $variantId,
                                'price' => (isset($price))?$price[$key]:"",
                                'qty' => (isset($qty))?$qty[$key]:"",
                                'type' => 'import',
                                'warehouse_id' => $id,
                                'created_at' => date('Y-m-d H:i:s'),
                            ]
                        );
                    }
                }
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Thêm thành công!',
                'url' => '/admin/import-goods'
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'errors' => array('alert' => array('0' => 'Thêm không thành công!'))
            ]);
        }
    } 
    public function getVariants($productId){
        $variants = Variant::select('id','sku','option1_value')
            ->where('product_id', $productId)
            ->orderBy('option1_value', 'asc')
            ->get();
        
        $options = '<option value="">-- Chọn phân loại --</option>';
        foreach($variants as $variant){
            $optionValue = $variant->option1_value ?? 'Mặc định';
            $options .= '<option value="'.$variant->id.'">'.$optionValue.'</option>';
        }
        
        return response()->json([
            'variants' => $options
        ]);
    }
    
    public function getVariantStock($variantId){
        $import = countProduct($variantId, 'import');
        $export = countProduct($variantId, 'export');
        $stock = $import - $export;
        
        return response()->json([
            'stock' => max(0, $stock)
        ]);
    }

    public function loadAdd(){
        $data['products'] = Product::select('id','name')->where('type','product')->orderBy('name','asc')->get();
        return view('Warehouse::import.loadAdd',$data);
    }

    public function show(Request $request){
        $detail = Warehouse::find($request->id);
        $data['detail'] = $detail;
        $data['products'] = ProductWarehouse::with(['variant.product:id,name'])->where([['type','import'],['warehouse_id',$request->id]])->get();
        $data['receipt_code'] = getImportReceiptCode($detail->id, $detail->created_at);
        $data['vat_invoice'] = getVatInvoiceFromContent($detail->content);
        $data['view_url'] = url('/admin/import-goods/print/' . $detail->id);
        $data['qr_code'] = generateQRCode($data['view_url'], 120);
        return view('Warehouse::import.show',$data);
    }
    public function print($id){
        $detail = Warehouse::find($id);
        $data['detail'] = $detail;
        $data['products'] = ProductWarehouse::with(['variant.product:id,name'])->where([['type','import'],['warehouse_id',$id]])->get();
        $data['receipt_code'] = getImportReceiptCode($detail->id, $detail->created_at);
        $data['vat_invoice'] = getVatInvoiceFromContent($detail->content);
        $data['view_url'] = url('/admin/import-goods/print/' . $detail->id);
        $data['qr_code'] = generateQRCode($data['view_url'], 120);
        return view('Warehouse::import.print',$data);
    }
    public function edit($id){
        active('warehouse','import-goods');
        $detail = Warehouse::find($id);
        if(!isset($detail) && empty($detail)){
            return redirect('admin/import-goods');
        }
        $data['detail'] = $detail;
        $data['products'] = Product::select('id','name')->where('type','product')->orderBy('name','asc')->get();
       	$data['list'] = ProductWarehouse::with(['variant.product:id,name'])->where([['type','import'],['warehouse_id',$id]])->orderBy('created_at','desc')->get();
        return view('Warehouse::import.edit',$data);
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
            $variants = $request->variant_id;
            $price = $request->price;
            $qty = $request->qty;
            if(isset($variants) && !empty($variants)){
                foreach ($variants as $key => $variantId) {
                    if($variantId && $variantId != ''){
                        ProductWarehouse::insertGetId(
                            [
                                'variant_id' => $variantId,
                                'price' => (isset($price))?$price[$key]:"",
                                'qty' => (isset($qty))?$qty[$key]:"",
                                'type' => 'import',
                                'warehouse_id' => $request->id,
                                'created_at' => date('Y-m-d H:i:s'),
                            ]
                        );
                    }
                }
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Sửa thành công!',
                'url' => '/admin/import-goods'
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
            $url = '/admin/import-goods?page='.$request->page;
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
            'url' => '/admin/import-goods'
        ]);
    }
    public function product(Request $request){
        active('warehouse','import-goods');
        $data['list'] = ProductWarehouse::join('posts', 'posts.id', '=', 'product_warehouse.product_id')->select('product_warehouse.*','posts.name','posts.sku')->where('product_warehouse.type','import')->where(function ($query) use ($request) {
            if($request->get('keyword') != "") {
                $query->where('name','like','%'.$request->get('keyword').'%')->orWhere('sku','like','%'.$request->get('keyword').'%');
            }
        })->orderBy('product_warehouse.created_at','desc')->paginate(10);
        return view('admin.import_goods.product.index',$data);
    }
    public function deleteProduct(Request $request){
        $data = ProductWarehouse::findOrFail($request->id)->delete();
        if($request->page !=""){
            $url = '/admin/import-goods?page='.$request->page;
        }else{
            $url = '/admin/import-goods/product';
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
            'url' => '/admin/import-goods/product'
        ]);
    }
    public function exportProduct(Request $request){

    }
}