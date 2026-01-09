<?php

namespace App\Modules\Product\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Modules\Brand\Models\Brand;
use App\Modules\Color\Models\Color;
use App\Modules\Size\Models\Size;
use App\Modules\Origin\Models\Origin;
use Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\OrderDetail;
use Illuminate\Support\Facades\Cache;
use App\Modules\Redirection\Models\Redirection;

class ProductController extends Controller
{
    private $model;
    private $module = 'Product';

    public function __construct(Product $model)
    {
        $this->model = $model;
    }

    public function index(Request $request)
    {
        active('product', 'list');
        $query = $this->model::where('type', 'product');

        if ($request->get('status') != "") {
            $query->where('status', $request->get('status'));
        }
        if ($request->get('cat_id') != "") {
            $query->where('cat_id', 'like', '%' . $request->get('cat_id') . '%');
        }
        if ($request->get('keyword') != "") {
            $query->where('name', 'like', '%' . $request->get('keyword') . '%');
        }

        $data['products'] = $query->orderBy('sort', 'desc')
            ->paginate(10)
            ->appends([
                'cat_id' => $request->get('cat_id'),
                'keyword' => $request->get('keyword'),
                'status' => $request->get('status')
            ]);

        $data['categories'] = $this->model::where([['type', 'taxonomy'], ['status', '1']])->orderBy('sort', 'asc')->get();
        return view($this->module . '::index', $data);
    }

    public function create()
    {
        active('product', 'list');
        $data['categories'] = $this->model::where([['type', 'taxonomy'], ['status', '1'], ['cat_id', '0']])->orderBy('sort', 'asc')->get();
        $data['brands'] = Brand::where('status', '1')->orderBy('name', 'asc')->get();
        $data['origins'] = Origin::where('status', '1')->orderBy('sort', 'asc')->get();
        return view($this->module . '::create', $data);
    }

    public function edit($id)
    {
        active('product', 'list');
        $detail = $this->model::find($id);
        if (!$detail) {
            return redirect()->route('product');
        }
        $data['categories'] = $this->model::where([['type', 'taxonomy'], ['status', '1'], ['cat_id', '0']])->orderBy('sort', 'asc')->get();
        $data['detail'] = $detail;
        $data['brands'] = Brand::where('status', '1')->orderBy('name', 'asc')->get();
        $data['origins'] = Origin::where('status', '1')->orderBy('sort', 'asc')->get();
        $data['gallerys'] = json_decode($detail->gallery);
        $data['dcat'] = json_decode($detail->cat_id);
        return view($this->module . '::edit', $data);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:250',
            'slug' => 'required|min:1|max:250|unique:posts,slug,' . $request->id,
        ], [
            'name.required' => 'Tiêu đề không được bỏ trống.',
            'name.min' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'name.max' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'slug.required' => 'Bạn chưa nhập đường dẫn',
            'slug.min' => 'Đường dẫn có độ dài từ 1 đến 250 ký tự',
            'slug.max' => 'Đường dẫn có độ dài từ 1 đến 250 ký tự',
            'slug.unique' => 'Đường dẫn đã tồn tại',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        // Logic: First image in gallery is the main image (Avatar)
        $gallery = $request->imageOther ?? [];
        $image = (count($gallery) > 0) ? $gallery[0] : null;

        // Get old product to check slug change
        $oldProduct = $this->model::find($request->id);
        $oldSlug = $oldProduct->slug;

        $this->model::where('id', $request->id)->update([
            'name' => $request->name,
            'slug' => $request->slug,
            'image' => $image,
            'content' => $request->content,
            'cbmp' => $request->cbmp,
            'description' => $request->description ?? $oldProduct->description,
            'status' => $request->status ?? $oldProduct->status,
            'cat_id' => json_encode($request->cat_id),
            'origin_id' => $request->origin_id,
            'brand_id' => $request->brand_id ?? $oldProduct->brand_id,
            'seo_title' => $request->seo_title ?? $oldProduct->seo_title,
            'seo_description' => $request->seo_description ?? $oldProduct->seo_description,
            'type' => 'product',
            'feature' => $request->feature ?? $oldProduct->feature,
            'stock' => $request->stock ?? $oldProduct->stock,
            'best' => $request->best ?? $oldProduct->best,
            'ingredient' => $request->ingredient ?? $oldProduct->ingredient,
            'verified' => $request->verified ?? $oldProduct->verified,
            'gallery' => json_encode($gallery),
            'user_id' => Auth::id(),
        ]);

        // Handle Redirection if slug changed
        if ($oldSlug != $request->slug) {
            try {
                // Check if redirection already exists to avoid duplicates
                $exists = Redirection::where('link_from', url($oldSlug))->exists();
                if (!$exists) {
                    Redirection::insert([
                        'link_from' => url($oldSlug),
                        'link_to' => url($request->slug),
                        'type' => 301,
                        'status' => '1',
                        'user_id' => Auth::id(),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
            } catch (\Exception $e) {
                // Log error but don't stop the process
                \Illuminate\Support\Facades\Log::error("Failed to create redirection: " . $e->getMessage());
            }
        }

        // Clear Cache
        Cache::flush();

        return response()->json([
            'status' => 'success',
            'alert' => 'Sửa thành công!',
            'url' => '/admin/product/edit/' . $request->id
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:250',
            'slug' => 'required|min:1|max:250|unique:posts,slug',
        ], [
            'name.required' => 'Tiêu đề không được bỏ trống.',
            'name.min' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'name.max' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'slug.required' => 'Bạn chưa nhập đường dẫn',
            'slug.min' => 'Đường dẫn có độ dài từ 1 đến 250 ký tự',
            'slug.max' => 'Đường dẫn có độ dài từ 1 đến 250 ký tự',
            'slug.unique' => 'Đường dẫn đã tồn tại',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        if ($request->sku) {
            $check = Variant::select('id')->where('sku', $request->sku)->exists();
            if ($check) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sku đã tồn tại'
                ]);
            }
        }

        // Logic: First image in gallery is the main image (Avatar)
        $gallery = $request->imageOther ?? [];
        $image = (count($gallery) > 0) ? $gallery[0] : null;

        try {
            $id = $this->model::insertGetId([
                'name' => $request->name,
                'slug' => $request->slug,
                'image' => $image,
                'content' => $request->content,
                'cbmp' => $request->cbmp,
                'description' => $request->description ?? '',
                'status' => $request->status ?? '1',
                'cat_id' => json_encode($request->cat_id ?? []),
                'seo_title' => $request->seo_title,
                'seo_description' => $request->seo_description,
                'origin_id' => $request->origin_id,
                'brand_id' => $request->brand_id,
                'type' => 'product',
                'feature' => $request->feature ?? '0',
                'ingredient' => $request->ingredient,
                'best' => $request->best ?? '0',
                'stock' => $request->stock ?? '1',
                'user_id' => Auth::id(),
                'gallery' => json_encode($gallery),
                'verified' => $request->verified ?? '0',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi DB Product: ' . $e->getMessage()
            ]);
        }

        if ($id > 0) {
            try {
                Variant::insertGetId([
                    'sku' => $request->sku ?? 'SKU-'.time().'-'.rand(10,99),
                    'product_id' => $id,
                    'image' => $image, // Use the main image from gallery
                    'weight' => ($request->weight != "") ? $request->weight : 0,
                    'size_id' => '0',
                    'color_id' => '0',
                    'price' => ($request->price != "") ? str_replace(',', '', $request->price) : 0,
                    'sale' => ($request->sale != "") ? str_replace(',', '', $request->sale) : 0,
                    'user_id' => Auth::id(),
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Lỗi DB Variant: ' . $e->getMessage()
                ]);
            }
            
            // Clear Cache
            Cache::flush();

            return response()->json([
                'status' => 'success',
                'alert' => 'Thêm thành công!',
                'url' => route('product')
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Thêm không thành công!']]
            ]);
        }
    }

    public function delete(Request $request)
    {
        $this->model::findOrFail($request->id)->delete();
        $url = route('product');
        if ($request->page != "") {
            $url .= '?page=' . $request->page;
        }
        return response()->json([
            'status' => 'success',
            'alert' => 'Xóa thành công!',
            'url' => $url
        ]);
    }

    public function status(Request $request)
    {
        $this->model::where('id', $request->id)->update([
            'status' => $request->status
        ]);
        return response()->json([
            'status' => 'success',
            'alert' => 'Đổi trạng thái thành công!',
            'url' => route('product')
        ]);
    }

    public function action(Request $request)
    {
        $check = $request->checklist;
        if (empty($check)) {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Chưa chọn dữ liệu cần thao tác!']]
            ]);
        }
        $action = $request->action;
        if ($action == 0) {
            $this->model::whereIn('id', $check)->update(['status' => '0']);
            return response()->json([
                'status' => 'success',
                'alert' => 'Ẩn thành công!',
                'url' => route('product')
            ]);
        } elseif ($action == 1) {
            $this->model::whereIn('id', $check)->update(['status' => '1']);
            return response()->json([
                'status' => 'success',
                'alert' => 'Hiển thị thành công!',
                'url' => route('product')
            ]);
        } else {
            $this->model::whereIn('id', $check)->delete();
            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => route('product')
            ]);
        }
    }

    public function postSort(Request $req)
    {
        $sort = $req->sort;
        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $key => $val) {
                $this->model::where('id', $key)->update([
                    'sort' => $val
                ]);
            }
        }
    }

    public function sort(Request $req)
    {
        $this->model::where('id', $req->id)->update([
            'sort' => $req->sort
        ]);
        return response()->json([
            'status' => 'success',
            'alert' => 'Đổi thứ tự thành công!',
            'url' => route('product')
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'files' => 'required',
            'files.*' => 'mimes:jpeg,png,jpg,gif,webp'
        ]);
        
        $insert = [];
        if ($request->TotalFiles > 0) {
            for ($x = 0; $x < $request->TotalFiles; $x++) {
                if ($request->hasFile('files' . $x)) {
                    $file = $request->file('files' . $x);
                    $name = $file->getClientOriginalName();
                    
                    // Upload to R2
                    try {
                        // $path = $file->storeAs('images/image', $name, 'r2');
                        // Fix for R2: Use put() directly to avoid storeAs() issues returning false
                        $filePath = 'images/image/' . $name;
                        $result = Storage::disk('r2')->put($filePath, file_get_contents($file));
                        
                        if ($result) {
                            $url = Storage::disk('r2')->url($filePath);
                            $insert[$x] = $url;
                        } else {
                            throw new \Exception("Storage::put returned false for $name");
                        }
                    } catch (\Exception $e) {
                         \Illuminate\Support\Facades\Log::error("R2 Upload Error: " . $e->getMessage());
                         return response()->json(["message" => "Lỗi upload: " . $e->getMessage()], 500);
                    }
                }
            }
            return response()->json($insert);
        } else {
            return response()->json(["message" => "Xin vui lòng thử lại."]);
        }
    }

    public function variant($id, $code)
    {
        active('product', 'list');
        $product = $this->model::find($id);
        $detail = Variant::find($code);
        if (!$product || !$detail) {
            return redirect()->route('product');
        }
        $data['detail'] = $detail;
        $data['product'] = $product;
        $data['variants'] = $product->variants;
        $data['colors'] = Color::where('status', '1')->get();
        $data['sizes'] = Size::where('status', '1')->orderBy('sort', 'asc')->get();
        return view($this->module . '::variant', $data);
    }

    public function editvariant(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'sku' => 'required|unique:variants,sku,' . $req->id,
        ], [
            'sku.required' => 'Bạn chưa nhập Sku',
            'sku.unique' => 'Sku đã tồn tại',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }
        $update = Variant::where('id', $req->id)->update([
            'sku' => $req->sku,
            'size_id' => $req->size_id,
            'color_id' => $req->color_id,
            'image' => $req->image,
            'weight' => $req->weight,
            'price' => str_replace(',', '', $req->price),
            'sale' => str_replace(',', '', $req->sale),
            'user_id' => Auth::id(),
        ]);
        if ($update > 0) {
            return response()->json([
                'status' => 'success',
                'alert' => 'Cập nhật biến thể thành công!',
                'url' => route('product.variant', ['id' => $req->product_id, 'code' => $req->id])
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Cập nhật biến thể không thành công!']]
            ]);
        }
    }

    public function variantnew($id)
    {
        active('product', 'list');
        $product = $this->model::find($id);
        if (!$product) {
            return redirect()->route('product');
        }
        $data['product'] = $product;
        $data['variants'] = $product->variants;
        $data['first'] = Variant::where('product_id', $id)->orderBy('id', 'asc')->first();
        $data['colors'] = Color::where('status', '1')->get();
        $data['sizes'] = Size::where('status', '1')->orderBy('sort', 'asc')->get();
        return view($this->module . '::variantnew', $data);
    }

    public function createvariant(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'sku' => 'required|unique:variants,sku',
        ], [
            'sku.required' => 'Bạn chưa nhập Sku',
            'sku.unique' => 'Sku đã tồn tại',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }
        $id = Variant::insertGetId([
            'sku' => $req->sku,
            'product_id' => $req->product_id,
            'image' => $req->image,
            'size_id' => $req->size_id,
            'color_id' => $req->color_id,
            'weight' => $req->weight,
            'price' => str_replace(',', '', $req->price),
            'sale' => str_replace(',', '', $req->sale),
            'user_id' => Auth::id(),
            'created_at' => date('Y-m-d H:i:s')
        ]);
        if ($id > 0) {
            return response()->json([
                'status' => 'success',
                'alert' => 'Tạo biến thể thành công!',
                'url' => route('product.variant', ['id' => $req->product_id, 'code' => $id])
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Tạo biến thể không thành công!']]
            ]);
        }
    }

    public function delvariant(Request $req)
    {
        $order = OrderDetail::select('id')->where('product_id', $req->id)->exists();
        if ($order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sản phẩm đã có đơn hàng không thể xóa!'
            ]);
        } else {
            Variant::findOrFail($req->id)->delete();
            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
            ]);
        }
    }
}
