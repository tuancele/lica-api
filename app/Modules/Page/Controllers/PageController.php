<?php

declare(strict_types=1);

namespace App\Modules\Page\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Page\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

class PageController extends Controller
{
    private $model;
    private $controller = 'page';
    private $view = 'Page';

    public function __construct(Page $model)
    {
        $this->model = $model;
    }

    public function index(Request $request)
    {
        active('page', 'list');
        $data['list'] = $this->model::where('type', 'page')->where(function ($query) use ($request) {
            if ($request->get('status') != '') {
                $query->where('status', $request->get('status'));
            }
            if ($request->get('keyword') != '') {
                $query->where('name', 'like', '%'.$request->get('keyword').'%');
            }
        })->orderBy('id', 'desc')->paginate(10)->appends(['keyword' => $request->get('keyword'), 'status' => $request->get('status')]);

        return view($this->view.'::index', $data);
    }

    public function create()
    {
        active('page', 'list');

        return view($this->view.'::create');
    }

    public function edit($id)
    {
        active('page', 'list');
        $detail = $this->model::where([['type', 'page'], ['id', $id]])->first();
        if (! isset($detail) && empty($detail)) {
            return redirect()->route('page');
        }
        $data['detail'] = $detail;

        return view($this->view.'::edit', $data);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:250',
            'slug' => 'required|min:1|max:250|unique:posts,slug,'.$request->id,
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
                'errors' => $validator->errors(),
            ]);
        }

        $this->model::where('id', $request->id)->update([
            'name' => $request->name,
            'slug' => $request->slug,
            'image' => $request->image,
            'banner' => $request->banner,
            'link' => $request->link,
            'description' => $request->description,
            'content' => $request->content,
            'status' => $request->status,
            'seo_title' => $request->seo_title,
            'temp' => $request->temp,
            'type' => 'page',
            'seo_description' => $request->seo_description,
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'status' => 'success',
            'alert' => 'Sửa thành công!',
            'url' => route('page'),
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
                'errors' => $validator->errors(),
            ]);
        }
        $id = $this->model::insertGetId(
            [
                'name' => $request->name,
                'slug' => $request->slug,
                'image' => $request->image,
                'banner' => $request->banner,
                'link' => $request->link,
                'description' => $request->description,
                'content' => $request->content,
                'status' => $request->status,
                'seo_title' => $request->seo_title,
                'seo_description' => $request->seo_description,
                'temp' => $request->temp,
                'type' => 'page',
                'user_id' => Auth::id(),
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );
        if ($id > 0) {
            return response()->json([
                'status' => 'success',
                'alert' => 'Thêm thành công!',
                'url' => route('page'),
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
            $url = route('page').'?page='.$request->page;
        } else {
            $url = route('page');
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
            'url' => route('page'),
        ]);
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
                'alert' => 'Ẩn bài viết thành công!',
                'url' => route('page'),
            ]);
        } elseif ($action == 1) {
            foreach ($check as $key => $value) {
                $this->model::where('id', $value)->update([
                    'status' => '1',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Hiển thị bài viết thành công!',
                'url' => route('page'),
            ]);
        } else {
            foreach ($check as $key => $value) {
                $this->model::where('id', $value)->delete();
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa bài viết thành công!',
                'url' => route('page'),
            ]);
        }
    }
}
