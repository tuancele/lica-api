<?php

declare(strict_types=1);

namespace App\Modules\Download\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Download\Models\Download;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

class DownloadController extends Controller
{
    private $model;
    private $view = 'Download';

    public function __construct(Download $model)
    {
        $this->model = $model;
    }

    public function index(Request $request)
    {
        active('download', 'list');
        $data['posts'] = $this->model::where('type', 'download')->where(function ($query) use ($request) {
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
        return view($this->view.'::create');
    }

    public function edit($id)
    {
        $post = $this->model::find($id);
        if (! isset($post) && empty($post)) {
            redirect('admin/download');
        }
        $data['detail'] = $post;

        return view($this->view.'::edit', $data);
    }

    public function update(Request $request)
    {
        $post = $this->model::find($request->id);
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
            'description' => $request->description,
            'content' => $request->content,
            'status' => $request->status,
            'type' => 'download',
            'seo_title' => $request->seo_title,
            'seo_description' => $request->seo_description,
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'status' => 'success',
            'alert' => 'Sửa thành công!',
            'url' => '/admin/download',
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
                'description' => $request->description,
                'content' => $request->content,
                'status' => $request->status,
                'type' => 'download',
                'seo_title' => $request->seo_title,
                'seo_description' => $request->seo_description,
                'user_id' => Auth::id(),
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );
        if ($id > 0) {
            return response()->json([
                'status' => 'success',
                'alert' => 'Thêm thành công!',
                'url' => '/admin/download',
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
        $post = $this->model::find($request->id);
        $data = $this->model::findOrFail($request->id)->delete();
        if ($request->page != '') {
            $url = '/admin/download?page='.$request->page;
        } else {
            $url = '/admin/download';
        }

        return response()->json([
            'status' => 'success',
            'alert' => 'Xóa thành công!',
            'url' => $url,
        ]);
    }

    public function status(Request $request)
    {
        $post = $this->model::find($request->id);
        $this->model::where('id', $request->id)->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'status' => 'success',
            'alert' => 'Đổi trạng thái thành công!',
            'url' => '/admin/download',
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
                $post = $this->model::find($value);
                $this->model::where('id', $value)->update([
                    'status' => '0',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Ẩn thành công!',
                'url' => '/admin/download',
            ]);
        } elseif ($action == 1) {
            foreach ($check as $key => $value) {
                $post = $this->model::find($value);
                $this->model::where('id', $value)->update([
                    'status' => '1',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Hiển thị thành công!',
                'url' => '/admin/download',
            ]);
        } else {
            foreach ($check as $key => $value) {
                $post = $this->model::find($value);
                $this->model::where('id', $value)->delete();
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => '/admin/download',
            ]);
        }
    }
}
