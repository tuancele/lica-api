<?php

namespace App\Themes\Website\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Post\Models\Post;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Modules\Brand\Models\Brand;
use App\Modules\Rate\Models\Rate;
use App\Themes\Website\Models\Facebook;
use App\Modules\Compare\Models\Compare;
use App\Modules\Deal\Models\Deal;
use App\Modules\Deal\Models\ProductDeal;
use App\Modules\Deal\Models\SaleDeal;
use App\Services\Warehouse\WarehouseServiceInterface;
use Session;

class ProductController extends Controller
{
    public function show($slug)
    {
        // Try to find the product
        // IMPORTANT: Chỉ match sản phẩm thật sự, tránh nuốt slug của page/blog/taxonomy
        $post = Product::where([['slug', $slug], ['status', '1'], ['type', 'product']])->first();

        if ($post) {
            $watch = Session::get('product_watched', []);
            if (!in_array($post->id, $watch)) {
                array_push($watch, $post->id);
                Session::put('product_watched', $watch);
            }
            
            $data['detail'] = $post;
            $data['product_id'] = $post->id;
            $data['gallerys'] = json_decode($post->gallery);
            $variants = Variant::where('product_id', $post->id)->orderBy('position', 'asc')->orderBy('id', 'asc')->get();
            $first = $variants->first();
            // Nếu không có variant thì tạo object rỗng để tránh lỗi view
            if (!$first) {
                $first = new Variant();
                $first->price = 0;
                $first->sku = '';
            }
            $data['variants'] = $variants;
            $data['first'] = $first;
            
            $arrCate = json_decode($post->cat_id, true);
            $catid = (is_array($arrCate) && !empty($arrCate)) ? $arrCate[0] : ($post->cat_id ?? "");
            
            $data['rates'] = Rate::where([['status', '1'], ['product_id', $post->id]])->orderBy('created_at', 'desc')->limit(5)->get();
            $data['category'] = Post::select('id', 'name', 'slug', 'cat_id')->where([['type', 'taxonomy'], ['id', $catid]])->first();
            $data['products'] = Product::join('variants', 'variants.product_id', '=', 'posts.id')
                ->select('posts.id', 'posts.stock', 'posts.name', 'posts.slug', 'posts.image', 'posts.brand_id', 'variants.price as price', 'variants.size_id as size_id', 'variants.color_id as color_id')
                ->where([['status', '1'], ['type', 'product'], ['posts.id', '!=', $post->id]])
                ->where('cat_id', 'like', '%"' . $catid . '"%')
                ->groupBy('product_id')
                ->limit(9)
                ->orderBy('posts.created_at', 'desc')->get();
            
            // Legacy color/size selector (only for old variant mode)
            $data['colors'] = Variant::select('color_id')->where('product_id', $post->id)->distinct()->get();
            
            if (Session::has('product_watched')) {
                $data['watchs'] = Product::join('variants', 'variants.product_id', '=', 'posts.id')
                    ->select('posts.id', 'posts.name', 'posts.slug', 'posts.image', 'posts.brand_id', 'posts.stock', 'variants.price as price', 'variants.size_id as size_id', 'variants.color_id as color_id')
                    ->where([['status', '1'], ['type', 'product']])
                    ->whereIn('posts.id', Session::get('product_watched'))
                    ->groupBy('product_id')
                    ->orderBy('posts.created_at', 'desc')->get();
            }
            
            $data['t_rates'] = Rate::select('id', 'rate')->where([['status', '1'], ['product_id', $post->id]])->get();
            
            // Tracking
            $dataf = array(
                'product_id' => $post->id,
                'price' => $first->price ?? 0,
                'url' => $post->slug,
                'event' => 'ViewContent',
            );
            Facebook::track($dataf);

            $data['compares'] = Compare::where([['status', '1'], ['brand', strtolower($post->brand->name ?? '')], ['name', 'like', $post->name . '%']])->groupby('store_id')->distinct()->limit(5)->get();

            // Deal sốc
            $now = strtotime(date('Y-m-d H:i:s'));
            $deal_id = ProductDeal::where('product_id', $post->id)->where('status', 1)->pluck('deal_id')->toArray();
            $deal = Deal::whereIn('id', $deal_id)->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]])->first();
            if ($deal) {
                $data['deal'] = $deal;
                $saledeals = SaleDeal::with(['product', 'variant'])->where([['deal_id', $deal->id], ['status', '1']])->get();

                // Gắn trạng thái tồn kho/quỹ cho từng sản phẩm mua kèm
                $warehouse = app(WarehouseServiceInterface::class);
                $saledeals = $saledeals->map(function ($sale) use ($warehouse) {
                    $remaining = max(0, ((int)$sale->qty) - ((int)($sale->buy ?? 0)));
                    $stock = 0;
                    try {
                        if ($sale->variant_id) {
                            $stockInfo = $warehouse->getVariantStock($sale->variant_id);
                            $stock = (int)($stockInfo['current_stock'] ?? 0);
                        } elseif ($sale->product) {
                            $stock = (int)($sale->product->stock ?? 0);
                        }
                    } catch (\Throwable $e) {
                        // Nếu lỗi kho, coi như hết để an toàn
                        $stock = 0;
                    }

                    $sale->remaining_quota = $remaining;
                    $sale->physical_stock = $stock;
                    $sale->available = $remaining > 0 && $stock > 0;
                    return $sale;
                });

                $data['saledeals'] = $saledeals;
            }

            return view('Website::product.detail', $data);
        } else {
            // Nếu không phải sản phẩm, fallback về HomeController@post để giữ nguyên behavior cũ
            return app()->call(\App\Themes\Website\Controllers\HomeController::class.'@post', ['url' => $slug]);
        }
    }
}
