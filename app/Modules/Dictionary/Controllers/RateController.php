<?php

declare(strict_types=1);

namespace App\Modules\Dictionary\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dictionary\Models\IngredientRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

class RateController extends Controller
{
    private $model;
    private $controller = 'rate';
    private $view = 'Dictionary';

    public function __construct(IngredientRate $model)
    {
        $this->model = $model;
    }

    public function index(Request $request)
    {
        active('dictionary', 'rate');
        $data['list'] = $this->model::where(function ($query) use ($request) {
            if ($request->get('status') != '') {
                $query->where('status', $request->get('status'));
            }
            if ($request->get('keyword') != '') {
                $query->where('name', 'like', '%'.$request->get('keyword').'%');
            }
        })->orderBy('sort', 'asc')->paginate(40)->appends($request->query());

        return view($this->view.'::rate.index', $data);
    }

    public function create()
    {
        active('dictionary', 'rate');

        return view($this->view.'::rate.create');
    }

    public function edit($id)
    {
        active('dictionary', 'rate');
        $detail = $this->model::find($id);
        if (! isset($detail) && empty($detail)) {
            return redirect()->route('dictionary.rate');
        }
        $data['detail'] = $detail;

        return view($this->view.'::rate.edit', $data);
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
                'url' => route('dictionary.rate'),
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
                'url' => route('dictionary.rate'),
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
            $url = route('dictionary.rate').'?page='.$request->page;
        } else {
            $url = route('dictionary.rate');
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
            'url' => route('dictionary.rate'),
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

        return response()->json([
            'status' => 'success',
            'alert' => 'Cập nhật thành công!',
            'url' => '',
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
                'alert' => 'Ẩn thành công!',
                'url' => route('dictionary.rate'),
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
                'url' => route('dictionary.rate'),
            ]);
        } else {
            foreach ($check as $key => $value) {
                $this->model::where('id', $value)->delete();
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => route('dictionary.rate'),
            ]);
        }
    }
}
