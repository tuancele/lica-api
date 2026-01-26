<?php

declare(strict_types=1);

namespace App\Themes\website\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Brand\Models\Brand;
use App\Modules\Product\Models\Product;

class BrandController extends Controller
{
    public function index($url)
    {
        $detail = Brand::where([['slug', $url], ['status', '1']])->first();
        if (isset($detail) && ! empty($detail)) {
            $data['detail'] = $detail;
            $data['galleries'] = json_decode($detail->gallery);
            $data['total'] = Product::select('id')->where([['type', 'product'], ['status', '1'], ['brand_id', $detail->id]])->get()->count();
            $data['products'] = Product::where([['type', 'product'], ['status', '1'], ['brand_id', $detail->id], ['stock', '1']])->paginate(30);
            $data['stocks'] = Product::where([['type', 'product'], ['status', '1'], ['brand_id', $detail->id], ['stock', '0']])->get();

            return view('Website::brand.index', $data);
        } else {
            return view('Website::404');
        }
    }
}
