<?php

declare(strict_types=1);

namespace App\Modules\Redirection\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Redirection\Models\Redirection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

class RedirectionController extends Controller
{
    private $model;
    private $controller = 'redirection';
    private $view = 'Redirection';

    public function __construct(Redirection $model)
    {
        $this->model = $model;
    }

    public function index(Request $request)
    {
        active('redirection', 'redirection');
        $data['list'] = $this->model::where(function ($query) use ($request) {
            if ($request->get('status') != '') {
                $query->where('status', $request->get('status'));
            }
            if ($request->get('keyword') != '') {
                $query->where('link_from', 'like', '%'.$request->get('keyword').'%')->orWhere('link_to', 'like', '%'.$request->get('keyword').'%');
            }
        })->orderBy('created_at', 'desc')->paginate(10)->appends(['keyword' => $request->get('keyword'), 'status' => $request->get('status')]);

        return view($this->view.'::index', $data);
    }

    public function create()
    {
        active('redirection', 'redirection');

        return view($this->view.'::create');
    }

    public function edit($id)
    {
        active('redirection', 'redirection');
        $data['detail'] = $this->model::find($id);

        return view($this->view.'::edit', $data);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'link_from' => 'required',
            'link_to' => 'required',
        ], [
            'link_from.required' => 'Link gốc không được bỏ trống.',
            'link_to.required' => 'Link đích không được bỏ trống.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ]);
        }
        $up = $this->model::where('id', $request->id)->update([
            'link_from' => $request->link_from,
            'link_to' => $request->link_to,
            'type' => $request->type,
            'status' => $request->status,
            'user_id' => Auth::id(),
        ]);
        if ($up > 0) {
            return response()->json([
                'status' => 'success',
                'alert' => 'Sửa thành công!',
                'url' => '/admin/redirection',
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
            'link_from' => 'required',
            'link_to' => 'required',
        ], [
            'link_from.required' => 'Link gốc không được bỏ trống.',
            'link_to.required' => 'Link đích không được bỏ trống.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ]);
        }
        $id = $this->model::insertGetId(
            [
                'link_from' => $request->link_from,
                'link_to' => $request->link_to,
                'type' => $request->type,
                'status' => $request->status,
                'user_id' => Auth::id(),
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );
        if ($id > 0) {
            return response()->json([
                'status' => 'success',
                'alert' => 'Thêm thành công!',
                'url' => '/admin/redirection',
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
            $url = '/admin/redirection?page='.$request->page;
        } else {
            $url = '/admin/redirection';
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
            'url' => '/admin/redirection',
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
                'url' => '/admin/redirection',
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
                'url' => '/admin/redirection',
            ]);
        } else {
            foreach ($check as $key => $value) {
                $this->model::where('id', $value)->delete();
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => '/admin/redirection',
            ]);
        }
    }
}
