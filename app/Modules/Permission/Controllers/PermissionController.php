<?php

declare(strict_types=1);

namespace App\Modules\Permission\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Permission\Models\Permission;
use Illuminate\Http\Request;
use Validator;

class PermissionController extends Controller
{
    private $model;
    private $view = 'Permission';

    public function __construct(Permission $model)
    {
        $this->model = $model;
    }

    public function index(Request $request)
    {
        // $this->authorize('list-role');
        $data['list'] = $this->model::where('parent_id', 0)->where(function ($query) use ($request) {
            if ($request->get('status') != '') {
                $query->where('status', $request->get('status'));
            }
            if ($request->get('keyword') != '') {
                $query->where('name', 'like', '%'.$request->get('keyword').'%');
            }
        })->orderBy('sort', 'asc')->paginate(20);

        return view($this->view.'::index', $data);
    }

    public function parent(Request $request)
    {
        $data['detail'] = $this->model::find($request->id);
        $data['list'] = $this->model::where('parent_id', $request->id)->where(function ($query) use ($request) {
            if ($request->get('status') != '') {
                $query->where('status', $request->get('status'));
            }
            if ($request->get('keyword') != '') {
                $query->where('name', 'like', '%'.$request->get('keyword').'%');
            }
        })->orderBy('sort', 'asc')->get();

        return view($this->view.'::parent', $data);
    }

    public function create()
    {
        // $this->authorize('create-role');
        $data['permissions'] = Permission::where('parent_id', '0')->orderBy('sort', 'asc')->get();

        return view($this->view.'::create', $data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ], [
            'name.required' => 'Tên quyền không được bỏ trống',
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
                'title' => $request->title,
                'sort' => $request->sort,
                'parent_id' => $request->parent_id,
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );
        if ($id > 0) {
            return response()->json([
                'status' => 'success',
                'alert' => 'Thêm thành công!',
                'url' => '/admin/permission',
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Thêm không thành công!']],
            ]);
        }
    }

    public function edit($id)
    {
        $detail = $this->model::find($id);
        if (! isset($detail) && empty($detail)) {
            return redirect('admin/permission');
        }
        $data['permissions'] = Permission::where('parent_id', '0')->orderBy('sort', 'asc')->get();
        $data['detail'] = $detail;

        return view($this->view.'::edit', $data);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ], [
            'name.required' => 'Tên quyền không được bỏ trống',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ]);
        }
        $update = $this->model::where('id', $request->id)->update([
            'name' => $request->name,
            'title' => $request->title,
            'sort' => $request->sort,
            'parent_id' => $request->parent_id,
        ]);
        if ($update > 0) {
            return response()->json([
                'status' => 'success',
                'alert' => 'Sửa thành công!',
                'url' => '/admin/permission',
            ]);
        } else {
            return response()->json([
                'status' => 'success',
                'alert' => 'Sửa không thành công!',
                'url' => '/admin/permission',
            ]);
        }
    }

    public function delete(Request $request)
    {
        $detail = $this->model::find($request->id);
        if (isset($detail) && ! empty($detail)) {
            $delete = $this->model::findOrFail($request->id)->delete();
            if ($delete > 0) {
                return response()->json([
                    'status' => 'success',
                    'alert' => 'Xóa thành công!',
                    'url' => '/admin/permission',
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'alert' => 'Xóa không thành công!',
                    'url' => '/admin/permission',
                ]);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'alert' => 'Dữ liệu không tồn tại!',
                'url' => '/admin/permission',
            ]);
        }
    }

    public function sort(Request $request)
    {
        $sort = $request->sort;
        if (isset($sort) && ! empty($sort)) {
            foreach ($sort as $key => $value) {
                $this->model::where('id', $key)->update([
                    'sort' => $value,
                ]);
            }
        }
    }
}
