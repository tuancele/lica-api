<?php

declare(strict_types=1);

namespace App\Modules\Brand\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Brand\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use Validator;

class BrandController extends Controller
{
    private $model;
    private $controller = 'brand';
    private $view = 'Brand';

    public function __construct(Brand $model)
    {
        $this->model = $model;
    }

    public function index(Request $request)
    {
        active('product', 'brand');
        $data['list'] = $this->model::where(function ($query) use ($request) {
            if ($request->get('status') != '') {
                $query->where('status', $request->get('status'));
            }
            if ($request->get('keyword') != '') {
                $query->where('name', 'like', '%'.$request->get('keyword').'%');
            }
        })->orderBy('name', 'asc')->paginate(10)->appends(['keyword' => $request->get('keyword'), 'status' => $request->get('status')]);

        return view($this->view.'::index', $data);
    }

    public function create()
    {
        active('product', 'brand');

        return view($this->view.'::create');
    }

    public function edit($id)
    {
        active('product', 'brand');
        $detail = $this->model::find($id);
        $data['detail'] = $detail;
        $data['gallerys'] = json_decode($detail->gallery ?? '[]', true) ?? [];

        return view($this->view.'::edit', $data);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:250',
        ], [
            'name.required' => 'Tiêu đề không được bỏ trống.',
            'name.min' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'name.max' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ]);
        }

        // Handle R2 session URLs for gallery
        $imageOther = $request->imageOther ?? [];
        $imageOther = array_filter($imageOther, function ($url) {
            return ! empty($url) &&
                   strpos($url, 'blob:') === false &&
                   strpos($url, 'no-image.png') === false;
        });
        $imageOther = array_values($imageOther);

        // Check session for R2 uploaded URLs
        $sessionKeyInput = $request->input('r2_session_key');
        $sessionUrls = [];
        if ($sessionKeyInput) {
            $sessionKeys = is_array($sessionKeyInput) ? $sessionKeyInput : explode(',', $sessionKeyInput);
            $sessionKeys = array_filter(array_map('trim', $sessionKeys));
            foreach ($sessionKeys as $sessionKey) {
                $urlsFromKey = Session::get($sessionKey, []);
                if (! empty($urlsFromKey)) {
                    if (is_array($urlsFromKey)) {
                        $sessionUrls = array_merge($sessionUrls, $urlsFromKey);
                    } else {
                        $sessionUrls[] = $urlsFromKey;
                    }
                }
            }
        }

        // Merge form URLs and session URLs
        $sessionUrls = array_filter($sessionUrls, function ($url) {
            return ! empty($url) &&
                   strpos($url, 'blob:') === false &&
                   strpos($url, 'no-image.png') === false;
        });

        $allUrls = array_merge($imageOther, $sessionUrls);
        $gallery = array_values(array_unique($allUrls));

        $up = $this->model::where('id', $request->id)->update([
            'name' => $request->name,
            'slug' => $request->slug,
            'content' => $request->content,
            'image' => $request->image,
            'banner' => $request->banner,
            'gallery' => json_encode($gallery),
            'logo' => $request->logo,
            'seo_title' => $request->seo_title,
            'seo_description' => $request->seo_description,
            'status' => $request->status,
            'user_id' => Auth::id(),
        ]);

        // Clear session URLs after successful save
        if ($sessionKeyInput) {
            $sessionKeys = is_array($sessionKeyInput) ? $sessionKeyInput : explode(',', $sessionKeyInput);
            $sessionKeys = array_filter(array_map('trim', $sessionKeys));
            foreach ($sessionKeys as $sessionKey) {
                Session::forget($sessionKey);
            }
        }
        if ($up > 0) {
            return response()->json([
                'status' => 'success',
                'alert' => 'Sửa thành công!',
                'url' => route('brand'),
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Sửa không thành công!']],
            ]);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:250',
        ], [
            'name.required' => 'Tiêu đề không được bỏ trống.',
            'name.min' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'name.max' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ]);
        }

        // Handle R2 session URLs for gallery
        $imageOther = $request->imageOther ?? [];
        $imageOther = array_filter($imageOther, function ($url) {
            return ! empty($url) &&
                   strpos($url, 'blob:') === false &&
                   strpos($url, 'no-image.png') === false;
        });
        $imageOther = array_values($imageOther);

        // Check session for R2 uploaded URLs
        $sessionKeyInput = $request->input('r2_session_key');
        $sessionUrls = [];
        if ($sessionKeyInput) {
            $sessionKeys = is_array($sessionKeyInput) ? $sessionKeyInput : explode(',', $sessionKeyInput);
            $sessionKeys = array_filter(array_map('trim', $sessionKeys));
            foreach ($sessionKeys as $sessionKey) {
                $urlsFromKey = Session::get($sessionKey, []);
                if (! empty($urlsFromKey)) {
                    if (is_array($urlsFromKey)) {
                        $sessionUrls = array_merge($sessionUrls, $urlsFromKey);
                    } else {
                        $sessionUrls[] = $urlsFromKey;
                    }
                }
            }
        }

        // Merge form URLs and session URLs
        $sessionUrls = array_filter($sessionUrls, function ($url) {
            return ! empty($url) &&
                   strpos($url, 'blob:') === false &&
                   strpos($url, 'no-image.png') === false;
        });

        $allUrls = array_merge($imageOther, $sessionUrls);
        $gallery = array_values(array_unique($allUrls));

        $id = $this->model::insertGetId(
            [
                'name' => $request->name,
                'slug' => $request->slug,
                'content' => $request->content,
                'image' => $request->image,
                'banner' => $request->banner,
                'gallery' => json_encode($gallery),
                'logo' => $request->logo,
                'seo_title' => $request->seo_title,
                'seo_description' => $request->seo_description,
                'status' => $request->status,
                'user_id' => Auth::id(),
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );

        // Clear session URLs after successful save
        if ($sessionKeyInput) {
            $sessionKeys = is_array($sessionKeyInput) ? $sessionKeyInput : explode(',', $sessionKeyInput);
            $sessionKeys = array_filter(array_map('trim', $sessionKeys));
            foreach ($sessionKeys as $sessionKey) {
                Session::forget($sessionKey);
            }
        }
        if ($id > 0) {
            return response()->json([
                'status' => 'success',
                'alert' => 'Thêm thành công!',
                'url' => route('brand'),
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Thêm không thành công!']],
            ]);
        }
    }

    public function delete(Request $request)
    {
        $data = $this->model::findOrFail($request->id)->delete();
        if ($request->page != '') {
            $url = route('brand').'?page='.$request->page;
        } else {
            $url = route('brand');
        }

        return response()->json([
            'status' => 'success',
            'alert' => 'Xóa thành công!',
            'url' => $url,
        ]);
    }

    public function status(Request $request)
    {
        $this->model::where('id', $request->id)->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'status' => 'success',
            'alert' => 'Đổi trạng thái thành công!',
            'url' => route('brand'),
        ]);
    }

    public function sort(Request $req)
    {
        $sort = $req->sort;
        if (isset($sort) && ! empty($sort)) {
            foreach ($sort as $key => $value) {
                $this->model::where('id', $key)->update([
                    'sort' => $value,
                ]);
            }
        }
    }

    public function action(Request $request)
    {
        $check = $request->checklist;
        if (! isset($check) && empty($check)) {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Chưa chọn dữ liệu cần thao tác!']],
            ]);
        }
        $action = $request->action;
        if ($action == 0) {
            foreach ($check as $key => $value) {
                $this->model::where('id', $value)->update([
                    'status' => '0',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Ẩn thành công!',
                'url' => route('brand'),
            ]);
        } elseif ($action == 1) {
            foreach ($check as $key => $value) {
                $this->model::where('id', $value)->update([
                    'status' => '1',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Hiển thị thành công!',
                'url' => route('brand'),
            ]);
        } else {
            foreach ($check as $key => $value) {
                $this->model::where('id', $value)->delete();
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => route('brand'),
            ]);
        }
    }

    public function upload(Request $request)
    {
        $validatedData = $request->validate([
            'files' => 'required',
            'files.*' => 'mimes:jpeg,png,jpg,gif,webp',
        ]);
        if ($request->TotalFiles > 0) {
            for ($x = 0; $x < $request->TotalFiles; $x++) {
                if ($request->hasFile('files'.$x)) {
                    $file = $request->file('files'.$x);
                    $name = $file->getClientOriginalName();
                    $path = '/uploads/images/image/';
                    $file->move('uploads/images/image', $name);
                    $insert[$x] = $path.$name;
                }
            }

            return response()->json($insert);
        } else {
            return response()->json(['message' => 'Xin vui lòng thử lại.']);
        }
    }
}
