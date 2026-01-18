<?php

namespace App\Modules\Deal\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Deal\Models\Deal;
use App\Modules\Product\Models\Product;
use App\Modules\Deal\Models\ProductDeal;
use App\Modules\Deal\Models\SaleDeal;
use App\Modules\Brand\Models\Brand;
use Validator;
use Illuminate\Support\Facades\Auth;
use Session;
class DealController extends Controller
{
    private $model;
    private $controller = 'deal';
    private $view = 'Deal';
    private $limit = 10;
    public function __construct(Deal $model){
        $this->model = $model;
    }
    public function index(Request $request)
    {
        active('marketing','deal');
        $data['list'] = $this->model::where(function ($query) use ($request) {
            if($request->get('status') != "") {
                $query->where('status', $request->get('status'));
            }
            if($request->get('keyword') != "") {
                $query->where('name','like','%'.$request->get('keyword').'%');
            }
        })->latest()->paginate(20)->appends($request->query());
        return view($this->view.'::index',$data);
    }
    public function create(){
        active('marketing','deal');
        Session::forget('ss_sale_product');
        Session::forget('ss_product_deal');
        return view($this->view.'::create');
    }
    public function edit($id){
        active('marketing','deal');
        $detail = $this->model::find($id);
        if(!isset($detail) && empty($detail)){
            return redirect()->route('deal');
        }
        $data['detail'] = $detail;
        $productdeals = ProductDeal::with(['product', 'variant'])->where('deal_id',$detail->id)->get();
        $data['productdeals'] = $productdeals;
        
        // Build session array with variant_id format: "product_id" or "product_id_vvariant_id"
        $productDealSession = [];
        foreach($productdeals as $pd) {
            if($pd->variant_id) {
                $productDealSession[] = $pd->product_id . '_v' . $pd->variant_id;
            } else {
                $productDealSession[] = $pd->product_id;
            }
        }
        Session::put('ss_product_deal', $productDealSession);
        
        $saledeals = SaleDeal::with(['product', 'variant'])->where('deal_id',$detail->id)->get();
        $data['saledeals'] = $saledeals;
        
        // Build session array with variant_id format: "product_id" or "product_id_vvariant_id"
        $saleDealSession = [];
        foreach($saledeals as $sd) {
            if($sd->variant_id) {
                $saleDealSession[] = $sd->product_id . '_v' . $sd->variant_id;
            } else {
                $saleDealSession[] = $sd->product_id;
            }
        }
        Session::put('ss_sale_product', $saleDealSession);
        return view($this->view.'::edit',$data);
    }

    public function view($id){
        active('marketing','deal');
        $detail = $this->model::find($id);
        if(!isset($detail) && empty($detail)){
            return redirect()->route('deal');
        }
        $data['detail'] = $detail;
        $productdeals = ProductDeal::where('deal_id',$detail->id)->get();
        $data['productdeals'] = $productdeals;
        $saledeals = SaleDeal::where('deal_id',$detail->id)->get();
        $data['saledeals'] = $saledeals;
        return view($this->view.'::view',$data);
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
            'name' => $request->name,
            'start' => strtotime($request->start),
            'end' => strtotime($request->end),
            'status' => $request->status,
            'limited' => $request->limited,
            'user_id'=> Auth::id()
        ));
        if($up > 0){
            $pricesale =  $request->pricesale;
            $numbersale = $request->numbersale;
            $status2 = $request->status2;
            SaleDeal::where('deal_id',$request->id)->delete();
            if(isset($pricesale) && !empty($pricesale)){
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
                                    'price' => ($price != "")?str_replace(',','', $price):0,
                                    'qty' => (isset($numbersale[$key][$vId]) && !empty($numbersale[$key][$vId]))?$numbersale[$key][$vId]:'0',
                                    'status' => (isset($status2[$key][$vId]) && isset($status2[$key][$vId]))?$status2[$key][$vId]:'0',
                                    'created_at' => date('Y-m-d H:i:s')
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
                                'price' => ($value != "")?str_replace(',','', $value):0,
                                'qty' => (isset($numbersale) && !empty($numbersale))?$numbersale[$key]:'0',
                                'status' => (isset($status2) && isset($status2[$key]))?$status2[$key]:'0',
                                'created_at' => date('Y-m-d H:i:s')
                            ]
                        );
                    }
                }
            }
            $productid = $request->productid;
            $statusdeal = $request->statusdeal;
            ProductDeal::where('deal_id',$request->id)->delete();
            if(isset($productid) && !empty($productid)){
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
                            'status' => (isset($statusdeal) && isset($statusdeal[$productId]))?$statusdeal[$productId]:'0',
                            'created_at' => date('Y-m-d H:i:s')
                        ]
                    );
                }
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Sửa thành công!',
                'url' => route('deal.edit',['id' => $request->id])
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
                'name' => $request->name,
                'start' => strtotime($request->start),
                'end' => strtotime($request->end),
                'status' => $request->status,
                'limited' => $request->limited,
                'user_id'=> Auth::id(),
                'created_at' => date('Y-m-d H:i:s')
            ]
        );
        if($id > 0){
            $pricesale =  $request->pricesale;
            $numbersale = $request->numbersale;
            $status2 = $request->status2;
            if(isset($pricesale) && !empty($pricesale)){
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
                                    'price' => ($price != "")?str_replace(',','', $price):0,
                                    'qty' => (isset($numbersale[$key][$vId]) && !empty($numbersale[$key][$vId]))?$numbersale[$key][$vId]:'0',
                                    'status' => (isset($status2[$key][$vId]) && isset($status2[$key][$vId]))?$status2[$key][$vId]:'0',
                                    'created_at' => date('Y-m-d H:i:s')
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
                                'price' => ($value != "")?str_replace(',','', $value):0,
                                'qty' => (isset($numbersale) && !empty($numbersale))?$numbersale[$key]:'0',
                                'status' => (isset($status2) && isset($status2[$key]))?$status2[$key]:'0',
                                'created_at' => date('Y-m-d H:i:s')
                            ]
                        );
                    }
                }
            }
            $productid = $request->productid;
            $statusdeal = $request->statusdeal;
            if(isset($productid) && !empty($productid)){
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
                            'status' => (isset($statusdeal) && isset($statusdeal[$productId]))?$statusdeal[$productId]:'0',
                            'created_at' => date('Y-m-d H:i:s')
                        ]
                    );
                }
            }
            Session::forget('ss_sale_product');
            Session::forget('ss_product_deal');
            return response()->json([
                'status' => 'success',
                'alert' => 'Tạo thành công!',
                'url' => route('deal')
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
        ProductDeal::where('deal_id',$request->id)->delete();
        SaleDeal::where('deal_id',$request->id)->delete();
        if($request->page !=""){
            $url = route('deal').'?page='.$request->page;
        }else{
            $url = route('deal');
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
            'url' => route('deal')
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
                'url' => route('deal')
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
                'url' => route('deal')
            ]);
        }else{
            foreach($check as $key => $value){
                $this->model::where('id',$value)->delete();
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => route('deal')
            ]);
        }
    }

    public function showProduct($deal_id){
        $now = strtotime(date('Y-m-d H:i:s'));
        if(isset($deal_id) && $deal_id != ""){
            $sales = Deal::select('id')->where([['status','1'],['start','<=',$now],['end','>=',$now],['id','!=',$deal_id]])->get();
        }else{
            $sales = Deal::select('id')->where([['status','1'],['start','<=',$now],['end','>=',$now]])->get();
        }
        $mang = $sales->pluck('id')->toArray();
        $products = ProductDeal::select('product_id')->whereIn('deal_id',$mang)->get()->pluck('product_id')->toArray();
        return $products;
    }

    public function loadProduct(Request $request){
        $search = $request->search;
        $brand = $request->brand;
        $page = $request->page;
        $limit = $this->limit;
        $offset = ($page-1)*$limit;
        $mang = $this->showProduct($request->deal_id);
        $products = Product::select('id','name','image','stock','has_variants')->where([['status','1'],['type','product'],['stock','1']])->whereNotIn('id',$mang)->where(function ($query) use ($search,$brand) {
            if(isset($search) && $search != "") {
                $query->where('name','like','%'.$search.'%');
            }
            if(isset($brand) && $brand != "") {
                $query->where('brand_id',$brand);
            }
        })->limit($limit)->offset($offset)->get();
        
        // Load variants for products that have variants
        foreach ($products as $product) {
            if ($product->has_variants == 1) {
                $product->load('variants');
            }
        }
        $html = "";
        $total = Product::select('id')->where([['status','1'],['type','product'],['stock','1']])->whereNotIn('id',$mang)->where(function ($query) use ($search,$brand) {
            if(isset($search) && $search != "") {
                $query->where('name','like','%'.$search.'%');
            }
            if(isset($brand) && $brand != "") {
                $query->where('brand_id',$brand);
            }
        })->get()->count();
        $pages = ceil($total/$limit);
        $data['pages'] = $pages;
        $data['products'] = $products;
        $data['brands'] = Brand::select('id','name')->where('status','1')->orderBy('name','asc')->get();
        $data['deal_id'] = $request->deal_id;
        $view = view($this->view.'::products',$data)->render();
        return response()->json([
            'page' => $page,
            'search' => $search,
            'brand' => $brand,
            'html' => $view
        ]);
    }

    public function choseProduct(Request $request){
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
        $mang = Session::get('ss_product_deal');
        if(empty($mang)) {
            $mang = [];
        }
        
        // Parse product IDs and variant IDs from session
        $productIds = [];
        foreach($mang as $item) {
            if(strpos($item, '_v') !== false) {
                $parts = explode('_v', $item);
                $productIds[] = $parts[0];
            } else {
                $productIds[] = $item;
            }
        }
        
        $products = Product::select('id','name','image','has_variants')
            ->where('type','product')
            ->whereIn('id', $productIds)
            ->get();
        
        // Load variants for products that have variants
        foreach ($products as $product) {
            if ($product->has_variants == 1) {
                $product->load('variants');
            }
        }
        
        $data['products'] = $products;
        return view($this->view.'::loadproduct',$data);
    }

    public function delProduct(Request $request){
        if(Session::has('ss_product_deal')){
            $ss_product = Session::get('ss_product_deal');
            $mang = $request->mang;
            if(isset($mang) && !empty($mang)){
                foreach ($mang as $key => $value) {
                    $k = array_search($value,$ss_product);
                    unset($ss_product[$k]);
                }
            }
            Session::put('ss_product_deal',$ss_product);
        }
        print_r(Session::get('ss_product_deal'));
    }

    public function loadProduct2(Request $request){
        $search = $request->search;
        $brand = $request->brand;
        $page = $request->page;
        $limit = $this->limit;
        $offset = ($page-1)*$limit;
        $products = Product::select('id','name','image','stock','has_variants')->where([['status','1'],['type','product'],['stock','1']])->where(function ($query) use ($search,$brand) {
            if(isset($search) && $search != "") {
                $query->where('name','like','%'.$search.'%');
            }
            if(isset($brand) && $brand != "") {
                $query->where('brand_id',$brand);
            }
        })->limit($limit)->offset($offset)->get();
        
        // Load variants for products that have variants
        foreach ($products as $product) {
            if ($product->has_variants == 1) {
                $product->load('variants');
            }
        }
        $html = "";
        $mang = array();
        $total = Product::select('id')->where([['status','1'],['type','product'],['stock','1']])->where(function ($query) use ($search,$brand) {
            if(isset($search) && $search != "") {
                $query->where('name','like','%'.$search.'%');
            }
            if(isset($brand) && $brand != "") {
                $query->where('brand_id',$brand);
            }
        })->get()->count();
        $pages = ceil($total/$limit);
        $data['pages'] = $pages;
        $data['products'] = $products;
        $data['deal_id'] = $request->deal_id;
        $data['brands'] = Brand::select('id','name')->where('status','1')->orderBy('name','asc')->get();
        $view = view($this->view.'::products2',$data)->render();
        return response()->json([
            'page' => $page,
            'search' => $search,
            'brand' => $brand,
            'html' => $view
        ]);
    }

    public function choseProduct2(Request $request){
        // if(Session::has('ss_sale_product')){
        //     $ss_product = Session::get('ss_sale_product');
        //     if(isset($request->productid) && !empty($request->productid)){
        //         foreach ($request->productid as $key => $value) {
        //             if(in_array($value, $ss_product)){
        //             }else{
        //                 array_push($ss_product, $value);
        //             }
        //         }
        //     }
        //     Session::put('ss_sale_product',$ss_product);
        // }else{
        //     Session::put('ss_sale_product',$request->productid);
        // }
        $mang = Session::get('ss_sale_product');
        if(empty($mang)) {
            $mang = [];
        }
        
        // Parse product IDs and variant IDs from session
        $productIds = [];
        foreach($mang as $item) {
            if(strpos($item, '_v') !== false) {
                $parts = explode('_v', $item);
                $productIds[] = $parts[0];
            } else {
                $productIds[] = $item;
            }
        }
        
        $products = Product::select('id','name','image','has_variants')
            ->where('type','product')
            ->whereIn('id', $productIds)
            ->get();
        
        // Load variants for products that have variants
        foreach ($products as $product) {
            if ($product->has_variants == 1) {
                $product->load('variants');
            }
        }
        
        $data['products'] = $products;
        return view($this->view.'::load_product',$data);
    }

    public function delProduct2(Request $request){
        if(Session::has('ss_sale_product')){
            $ss_product = Session::get('ss_sale_product');
            $mang = $request->mang;
            if(isset($mang) && !empty($mang)){
                foreach ($mang as $key => $value) {
                    $k = array_search($value,$ss_product);
                    unset($ss_product[$k]);
                }
            }
            Session::put('ss_sale_product',$ss_product);
        }
    }

    public function addSession(Request $request){
        if(Session::has('ss_product_deal')){
            $ss_product = Session::get('ss_product_deal');
            $mang = $request->mang;
            if(isset($mang) && !empty($mang)){
                foreach ($mang as $key => $value) {
                    if(in_array($value, $ss_product)){
                    }else{
                        array_push($ss_product, $value);
                    }
                }
            }
            Session::put('ss_product_deal',$ss_product);
        }else{
            Session::put('ss_product_deal',$request->mang);
        }
    }

    public function delSession(Request $request){
        if(Session::has('ss_product_deal')){
            $ss_product = Session::get('ss_product_deal');
            $mang = $request->mang;
            if(isset($mang) && !empty($mang)){
                foreach ($mang as $key => $value) {
                    $k = array_search($value,$ss_product);
                    unset($ss_product[$k]);
                }
            }
            Session::put('ss_product_deal',$ss_product);
        }
    }

     public function addSession2(Request $request){
        if(Session::has('ss_sale_product')){
            $ss_product = Session::get('ss_sale_product');
            $mang = $request->mang;
            if(isset($mang) && !empty($mang)){
                foreach ($mang as $key => $value) {
                    if(in_array($value, $ss_product)){
                    }else{
                        array_push($ss_product, $value);
                    }
                }
            }
            Session::put('ss_sale_product',$ss_product);
        }else{
            Session::put('ss_sale_product',$request->mang);
        }
    }

    public function delSession2(Request $request){
        if(Session::has('ss_sale_product')){
            $ss_product = Session::get('ss_sale_product');
            $mang = $request->mang;
            if(isset($mang) && !empty($mang)){
                foreach ($mang as $key => $value) {
                    $k = array_search($value,$ss_product);
                    unset($ss_product[$k]);
                }
            }
            Session::put('ss_sale_product',$ss_product);
        }
    }

    
}
