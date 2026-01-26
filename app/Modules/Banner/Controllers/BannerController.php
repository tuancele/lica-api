<?php

declare(strict_types=1);

namespace App\Modules\Banner\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Banner\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Validator;

class BannerController extends Controller
{
    private $model;
    private $controller = 'banner';
    private $view = 'Banner';

    public function __construct(Banner $model)
    {
        $this->model = $model;
    }

    public function index(Request $request)
    {
        active('media', 'banner');
        $data['list'] = $this->model::where('type', 'banner')->where(function ($query) use ($request) {
            if ($request->get('status') != '') {
                $query->where('status', $request->get('status'));
            }
            if ($request->get('cat_id') != '') {
                $query->where('cat_id', $request->get('cat_id'));
            }
            if ($request->get('keyword') != '') {
                $query->where('name', 'like', '%'.$request->get('keyword').'%');
            }
        })->orderBy('name', 'asc')->paginate(10);

        return view($this->view.'::index', $data);
    }

    public function create()
    {
        active('media', 'banner');

        return view($this->view.'::create');
    }

    public function edit($id)
    {
        active('media', 'banner');
        $data['detail'] = $this->model::find($id);

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
        $up = $this->model::where('id', $request->id)->update([
            'name' => $request->name,
            'image' => $request->image,
            'link' => $request->link,
            'cat_id' => $request->cat_id,
            'type' => 'banner',
            'status' => $request->status,
            'user_id' => Auth::id(),
        ]);
        if ($up > 0) {
            Cache::forget('home_banners_v1');

            return response()->json([
                'status' => 'success',
                'alert' => 'Sửa thành công!',
                'url' => '/admin/'.$this->controller,
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
        $id = $this->model::insertGetId(
            [
                'name' => $request->name,
                'image' => $request->image,
                'link' => $request->link,
                'cat_id' => $request->cat_id,
                'type' => 'banner',
                'status' => $request->status,
                'user_id' => Auth::id(),
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );
        if ($id > 0) {
            Cache::forget('home_banners_v1');

            return response()->json([
                'status' => 'success',
                'alert' => 'Thêm thành công!',
                'url' => '/admin/'.$this->controller,
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
        Cache::forget('home_banners_v1');
        if ($request->page != '') {
            $url = '/admin/'.$this->controller.'?page='.$request->page;
        } else {
            $url = '/admin/'.$this->controller;
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
        Cache::forget('home_banners_v1');

        return response()->json([
            'status' => 'success',
            'alert' => 'Đổi trạng thái thành công!',
            'url' => '/admin/'.$this->controller,
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
            Cache::forget('home_banners_v1');
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
            Cache::forget('home_banners_v1');

            return response()->json([
                'status' => 'success',
                'alert' => 'Ẩn thành công!',
                'url' => '/admin/'.$this->controller,
            ]);
        } elseif ($action == 1) {
            foreach ($check as $key => $value) {
                $this->model::where('id', $value)->update([
                    'status' => '1',
                ]);
            }
            Cache::forget('home_banners_v1');

            return response()->json([
                'status' => 'success',
                'alert' => 'Hiển thị thành công!',
                'url' => '/admin/'.$this->controller,
            ]);
        } else {
            foreach ($check as $key => $value) {
                $this->model::where('id', $value)->delete();
            }
            Cache::forget('home_banners_v1');

            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => '/admin/'.$this->controller,
            ]);
        }
    }
}
