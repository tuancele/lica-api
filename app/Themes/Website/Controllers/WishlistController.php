<?php

declare(strict_types=1);

namespace App\Themes\Website\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Product;
use App\Themes\Website\Models\Facebook;
use App\Themes\Website\Models\Wishlist;
use Exception;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function get()
    {
        $member = auth()->guard('member')->user();
        $data['list'] = Wishlist::where('member_id', $member['id'])->get();

        return view('Website::wishlist.get', $data);
    }

    public function add(Request $request)
    {
        try {
            $member = auth()->guard('member')->user();
            $wishlist = Wishlist::where([['member_id', $member['id']], ['product_id', $request->id]])->get();
            $total = Wishlist::where('member_id', $member['id'])->count();

            $product = Product::select('id', 'slug')->where('id', $request->id)->first();

            if ($product) {
                $dataf = [
                    'product_id' => $product->id,
                    'price' => '0',
                    'url' => getSlug($product->slug),
                    'event' => 'AddToWishlist',
                ];
                Facebook::track($dataf);
            }

            if ($wishlist->count() > 0) {
                return $total;
            } else {
                Wishlist::insert([
                    'member_id' => $member['id'],
                    'product_id' => $request->id,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                return $total + 1;
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    public function remove(Request $request)
    {
        try {
            $member = auth()->guard('member')->user();
            $wishlist = Wishlist::where([['member_id', $member['id']], ['product_id', $request->id]])->first();

            if ($wishlist) {
                $wishlist->delete();
            }

            return Wishlist::where('member_id', $member['id'])->count();
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    public function removeAll()
    {
        try {
            $member = auth()->guard('member')->user();
            Wishlist::where('member_id', $member['id'])->delete();

            return '<div class="title-wish">
                <h3>Ưa thích</h3>
                <a href="javascript:;" class="remove-all-wishlist">Xóa hết</a>
            </div>
            <div class="list-wishlist"><p class="none-wishlist">Nhấn nút trái tim ở mỗi sản phẩm để lưu vào ưa thích</p></div>';
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }
}
