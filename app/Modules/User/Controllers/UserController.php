<?php

declare(strict_types=1);

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

class UserController extends Controller
{
    private function checkRole()
    {
        $user = Auth::user();
        if ($user['role_id'] != 1) {
            return false;
        }

        return true;
    }

    public function index(Request $request)
    {
        if (! $this->checkRole()) {
            return redirect('admin/dashboard');
        }

        active('user', 'user');
        $query = User::query();

        if ($request->get('status') != '') {
            $query->where('status', $request->get('status'));
        }
        if ($request->get('keyword') != '') {
            $keyword = $request->get('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('email', 'like', '%'.$keyword.'%')
                    ->orWhere('phone', 'like', '%'.$keyword.'%');
            });
        }

        $data['users'] = $query->orderBy('id', 'desc')->paginate(10);

        return view('User::index', $data);
    }

    public function create()
    {
        if (! $this->checkRole()) {
            return redirect('admin/dashboard');
        }
        active('user', 'user');
        $data = [];

        return view('User::create', $data);
    }

    public function edit($id)
    {
        if (! $this->checkRole()) {
            return redirect('admin/dashboard');
        }
        active('user', 'user');
        $data['detail'] = User::find($id);

        return view('User::edit', $data);
    }

    public function change($id)
    {
        $user = Auth::user();
        if ($user['role_id'] == 1) {
            active('user', 'user');
            $data['detail'] = User::find($id);

            return view('User::change', $data);
        } else {
            if ($user['id'] == $id) {
                active('dashboard', 'dashboard');
                $data['detail'] = User::find($id);

                return view('User::change', $data);
            } else {
                return redirect('admin/dashboard');
            }
        }
    }

    public function changepass(Request $request)
    {
        $user = Auth::user();
        $targetId = $request->id;

        // Validation
        $validator = Validator::make($request->all(), [
            'password' => 'required',
        ], [
            'password.required' => 'Bạn chưa nhập mật khẩu',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ]);
        }

        // Permission Check
        $allowed = false;
        $url = '/admin/dashboard';

        if ($user['role_id'] == 1) {
            $allowed = true;
            $url = '/admin/user';
        } elseif ($user['id'] == $targetId) {
            $allowed = true;
        }

        if ($allowed) {
            User::where('id', $targetId)->update([
                'password' => bcrypt($request->password),
            ]);

            return response()->json([
                'status' => 'success',
                'alert' => 'Đổi mật khẩu thành công!',
                'url' => $url,
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Bạn không có quyền đổi mật khẩu tài khoản này!']],
            ]);
        }
    }

    public function update(Request $request)
    {
        if (! $this->checkRole()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'phone' => 'required',
            'email' => 'required|unique:users,email,'.$request->id,
        ], [
            'name.required' => 'Bạn chưa nhập họ tên',
            'phone.required' => 'Bạn chưa nhập số điện thoại',
            'email.required' => 'Bạn chưa nhập email đăng nhập',
            'email.unique' => 'Email đăng nhập đã tồn tại',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ]);
        }

        User::where('id', $request->id)->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role_id' => $request->role_id,
            'status' => $request->status,
        ]);

        return response()->json([
            'status' => 'success',
            'alert' => 'Sửa thành công!',
            'url' => '/admin/user',
        ]);
    }

    public function store(Request $request)
    {
        if (! $this->checkRole()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'phone' => 'required',
            'password' => 'required',
            'email' => 'required|unique:users,email',
        ], [
            'name.required' => 'Bạn chưa nhập họ tên',
            'phone.required' => 'Bạn chưa nhập số điện thoại',
            'password.required' => 'Bạn chưa nhập mật khẩu',
            'email.required' => 'Bạn chưa nhập email đăng nhập',
            'email.unique' => 'Email đăng nhập đã tồn tại',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ]);
        }

        $id = User::insertGetId([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role_id' => $request->role_id,
            'password' => bcrypt($request->password),
            'status' => $request->status,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($id > 0) {
            return response()->json([
                'status' => 'success',
                'alert' => 'Tạo tài khoản thành công!',
                'url' => '/admin/user',
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
        if (! $this->checkRole()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if (Auth::id() == $request->id) {
            return response()->json([
                'status' => 'error',
                'alert' => 'Bạn không thể xóa chính mình!',
            ]);
        }

        User::findOrFail($request->id)->delete();

        $url = '/admin/user';
        if ($request->user != '') {
            $url .= '?user='.$request->user;
        }

        return response()->json([
            'status' => 'success',
            'alert' => 'Xóa thành công!',
            'url' => $url,
        ]);
    }

    public function status(Request $request)
    {
        if (! $this->checkRole()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if (Auth::id() == $request->id) {
            return response()->json([
                'status' => 'error',
                'alert' => 'Bạn không thể thay đổi trạng thái chính mình!',
            ]);
        }

        User::where('id', $request->id)->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'status' => 'success',
            'alert' => 'Đổi trạng thái thành công!',
            'url' => '/admin/user',
        ]);
    }

    public function checkemail(Request $req)
    {
        $count = User::where('email', $req->email)->count();

        return response()->json($count == 0);
    }

    public function checkemailedit(Request $req)
    {
        $count = User::where([['email', $req->email], ['id', '!=', $req->id]])->count();

        return response()->json($count == 0);
    }
}
