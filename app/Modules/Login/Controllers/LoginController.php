<?php

namespace App\Modules\Login\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function index(){
        if(Auth::check()){
            $user = Auth::user();
            if($user->status == 1){
                return redirect('admin/dashboard');
            }
        }
        return view('admin.login');
    }
    public function postLogin(Request $req){
        $this->validate($req,[
            'email' => 'required',
            'password' => 'required',
        ],[
            'email.required' => 'Bạn chưa nhập Email',
            'password.required' => 'Bạn chưa nhập mật khẩu',
        ]);
        if (Auth::attempt(['email' => $req->email, 'password' => $req->password], $req->remember)) {
            return redirect('admin/dashboard')->with('success','Đăng nhập thành công');
        }else{
            return redirect()->back()->with('status', 'Email hoặc mật khẩu không chính xác');
        }
    }
    public function logout(){
        Auth::logout();
        return redirect('admin/login');
    }
}
