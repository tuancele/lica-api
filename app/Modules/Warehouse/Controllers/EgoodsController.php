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
    	// Don't load all products to avoid overload - use AJAX search instead
    	$data['products'] = collect([]);
        return view('Warehouse::export.create',$data);
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
        $import = countProduct($variantId,'import');
        $export = countProduct($variantId,'export');
        $total = $import - $export;
        
        return response()->json([
            'stock' => max(0, $total)
        ]);
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
                'type' => 'export',
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
                        if(isset($price) && isset($qty)){
                            $total = countProduct($variantId,'import');
                            if($qty[$key] <= $total){
                                ProductWarehouse::insertGetId(
                                    [
                                        'variant_id' => $variantId,
                                        'price' => (isset($price))?$price[$key]:"",
                                        'qty' => (isset($qty))?$qty[$key]:"",
                                        'type' => 'export',
                                        'warehouse_id' => $id,
                                        'created_at' => date('Y-m-d H:i:s'),
                                    ]
                                );
                            }
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
    public function getVariant($id){
        $variant = Variant::find($id);
        if(isset($variant) && !empty($variant)){
            $optionValue = $variant->option1_value ?? 'Mặc định';
            
            return response()->json([
                'option1_value' => $optionValue
            ]);
        }
        return response()->json(['option1_value' => '']);
    }

    public function loadAdd(){
        // Don't load products - use AJAX search instead
        return view('Warehouse::export.loadAdd');
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
        if(!$detail) {
            return response()->json(['status' => 'error', 'message' => 'Phiếu xuất hàng không tồn tại']);
        }
        
        $data['detail'] = $detail;
        $data['products'] = ProductWarehouse::with(['variant.product:id,name'])->where([['type','export'],['warehouse_id',$request->id]])->get();
        
        // Calculate total
        $total = 0;
        foreach($data['products'] as $product) {
            $total += ($product->price * $product->qty);
        }
        $data['total'] = $total;
        
        // Get receipt code
        $data['receipt_code'] = getExportReceiptCode($detail->id, $detail->created_at);
        
        // Get VAT invoice from content
        $data['vat_invoice'] = getVatInvoiceFromContent($detail->content);
        
        // Generate QR code
        $viewUrl = url('/admin/export-goods/print/' . $detail->id);
        $data['view_url'] = $viewUrl;
        $data['qr_code'] = generateQRCode($viewUrl, 120);
        
        return view('Warehouse::export.show',$data);
    }
    
    public function print($id){
        $detail = Warehouse::find($id);
        if(!$detail) {
            abort(404);
        }
        
        $data['detail'] = $detail;
        $data['products'] = ProductWarehouse::with(['variant.product:id,name'])->where([['type','export'],['warehouse_id',$id]])->get();
        
        // Calculate total
        $total = 0;
        foreach($data['products'] as $product) {
            $total += ($product->price * $product->qty);
        }
        $data['total'] = $total;
        
        // Get receipt code
        $data['receipt_code'] = getExportReceiptCode($detail->id, $detail->created_at);
        
        // Get VAT invoice from content
        $data['vat_invoice'] = getVatInvoiceFromContent($detail->content);
        
        // Generate QR code
        $viewUrl = url('/admin/export-goods/print/' . $detail->id);
        $data['view_url'] = $viewUrl;
        $data['qr_code'] = generateQRCode($viewUrl, 100);
        
        return view('Warehouse::export.print',$data);
    }
    public function edit($id){
        active('warehouse','exportgoods');
        $detail = Warehouse::find($id);
        if(!isset($detail) && empty($detail)){
            return redirect('admin/export-goods');
        }
        $data['detail'] = $detail;
        $data['products'] = Variant::select('id','sku','option1_value','product_id')->with('product:id,name')->latest()->get();
       	$data['list'] = ProductWarehouse::with(['variant.product:id,name'])->where([['type','export'],['warehouse_id',$id]])->orderBy('created_at','desc')->get();
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
        // Combine VAT invoice and content
        $content = $request->content ?? '';
        if($request->vat_invoice) {
            $content = ($content ? $content . "\n" : '') . 'Số hóa đơn VAT: ' . $request->vat_invoice;
        }
        
        $update = Warehouse::where('id',$request->id)->update(array(
            'code' => $request->code,
            'subject' => $request->subject,
            'content' => $content,
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
                        if(isset($price) && isset($qty)){
                            $total = countProduct($variantId,'import');
                            if($qty[$key] <= $total){
                                ProductWarehouse::insertGetId(
                                    [
                                        'variant_id' => $variantId,
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
        $data['list'] = ProductWarehouse::with(['variant.product:id,name'])->where('type','export')->where(function ($query) use ($request) {
            if($request->get('keyword') != "") {
                $query->whereHas('variant.product', function($q) use ($request) {
                    $q->where('name','like','%'.$request->get('keyword').'%');
                })->orWhereHas('variant', function($q) use ($request) {
                    $q->where('sku','like','%'.$request->get('keyword').'%');
                });
            }
        })->orderBy('created_at','desc')->paginate(10);
        return view('Warehouse::export.product.index',$data);
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