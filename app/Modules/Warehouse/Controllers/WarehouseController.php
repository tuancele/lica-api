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
use App\Modules\Order\Models\OrderDetail;
use App\Modules\Warehouse\Models\Warehouse;

class WarehouseController extends Controller
{
	public function index(Request $request)
    {
        active('warehouse','warehouse');
        $data['list'] = Product::select('id','name', 'slug','image')->where('type','product')->where(function ($query) use ($request) {
            if($request->get('keyword') != "") {
	            $query->where('name','like','%'.$request->get('keyword').'%');
	        }
        })->orderBy('created_at','desc')->paginate(10);
        return view('Warehouse::index',$data);
    }
    public function statistical(Request $request){
        return view('Warehouse::statistical',$data);
    }
    public function revenue(Request $request){
        active('statistical','revenue');
        $data['list'] = Variant::join('posts', 'posts.id', '=', 'variants.product_id')->select('variants.*','posts.name as name', 'posts.slug as slug')->where(function ($query) use ($request) {
            if($request->get('keyword') != "") {
                $query->where('posts.name','like','%'.$request->get('keyword').'%')->orWhere('variants.sku','like','%'.$request->get('keyword').'%');;
            }
        })->orderBy('variants.created_at','desc')->paginate(10);
        return view('Warehouse::revenue',$data);
    }
    public function quantity(Request $request){
        active('statistical','quantity');
        $data['list'] = Variant::join('posts', 'posts.id', '=', 'variants.product_id')->select('variants.*','posts.name as name', 'posts.slug as slug')->where(function ($query) use ($request) {
            if($request->get('keyword') != "") {
                $query->where('posts.name','like','%'.$request->get('keyword').'%')->orWhere('variants.sku','like','%'.$request->get('keyword').'%');;
            }
        })->orderBy('variants.created_at','desc')->paginate(10);
        return view('Warehouse::quantity',$data);
    }
}