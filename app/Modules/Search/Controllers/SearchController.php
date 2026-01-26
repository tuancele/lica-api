<?php

declare(strict_types=1);

namespace App\Modules\Search\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Search\Models\Search;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

class SearchController extends Controller
{
    private $model;
    private $controller = 'search';
    private $view = 'Search';

    public function __construct(Search $model)
    {
        $this->model = $model;
    }

    public function index(Request $request)
    {
        active('product', 'search');
        $data['list'] = $this->model::where(function ($query) use ($request) {
            if ($request->get('status') != '') {
                $query->where('status', $request->get('status'));
            }
            if ($request->get('keyword') != '') {
                $query->where('name', 'like', '%'.$request->get('keyword').'%');
            }
        })->orderBy('sort', 'asc')->paginate(10)->appends($request->query());

        return view($this->view.'::index', $data);
    }

    public function create()
    {
        active('product', 'search');

        return view($this->view.'::create');
    }

    public function edit($id)
    {
        active('product', 'search');
        $detail = $this->model::find($id);
        if (! isset($detail) && empty($detail)) {
            return redirect()->route('search');
        }
        $data['detail'] = $detail;

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
            'status' => $request->status,
            'user_id' => Auth::id(),
        ]);
        if ($up > 0) {
            return response()->json([
                'status' => 'success',
                'alert' => 'Sửa thành công!',
                'url' => route('search'),
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
                'status' => $request->status,
                'user_id' => Auth::id(),
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );
        if ($id > 0) {
            return response()->json([
                'status' => 'success',
                'alert' => 'Thêm thành công!',
                'url' => route('search'),
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
            $url = route('search').'?page='.$request->page;
        } else {
            $url = route('search');
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
            'url' => route('search'),
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
                'url' => route('search'),
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
                'url' => route('search'),
            ]);
        } else {
            foreach ($check as $key => $value) {
                $this->model::where('id', $value)->delete();
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => route('search'),
            ]);
        }
    }
}
