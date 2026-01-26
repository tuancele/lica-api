<?php

declare(strict_types=1);
namespace App\Themes\website\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Member\Models\Member;
use App\Modules\Address\Models\Address;
use Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use App\Traits\Sendmail;
use App\Traits\MemberActive;

class LoginController extends Controller
{

    use Sendmail,MemberActive;
    public function login(Request $request){
       $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ],[
            'email.required' => 'Bạn chưa nhập email',
            'password.required' => 'Bạn chưa nhập mật khẩu',
        ]);
        if($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }
        if (Auth::guard('member')->attempt(['email' => $request->email, 'password' => $request->password,'status' => 1], $request->remember)) {
            if($request->returnUrl != "") {
                return response()->json([
                    'status' => 'success',
                    'url' => $request->returnUrl
                ]);
            }else{
                return response()->json([
                    'status' => 'success',
                    'url' => route('account.profile')
                ]);
            }
        }else{
            return response()->json([
                'status' => 'erorr',
                'message' => 'Email hoặc mật khẩu không chính xác'
            ]);
        }
    }

    public function register(Request $request){
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|unique:members,email',
            'password' => 'required',
        ],[
            'first_name.required' => 'Bạn chưa nhập họ',
            'last_name.required' => 'Bạn chưa nhập tên',
            'email.required' => 'Bạn chưa nhập địa chỉ email',
            'email.unique' => 'Email đã tồn tại. Nếu bạn quên mật khẩu, bạn có thể thiết lập lại mật khẩu.',
            'password.required' => 'Bạn chưa nhập mật khẩu'
        ]);
        if($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }
        $id = Member::insertGetId(
            [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'type' => '1',
                'status' => '0',
                'created_at' => date('Y-m-d H:i:s')
            ]
        );
        if($id > 0){
            $member = Member::find($id);
            $token = $this->createActivation($member);
            $data = array("name" => $request->name,'url' => asset('account/activation/'.$token));
            $this->send('Website::email.register','Kích hoạt tài khoản',$request->email,$data);
            return response()->json([
                'status' => 'success',
                'message' => 'Chúc mừng bạn đã đăng ký thành công! Bạn hãy kiểm tra email và thực hiện theo hướng dẫn để kích hoạt tài khoản'
            ]);
        }else{
            return response()->json([
                'status' => 'erorr',
                'message' => 'Đăng ký không thành công, xin vui lòng thử lại'
            ]);
        }
    }

    public function redirect($provider){
        return Socialite::driver($provider)->redirect();
    }
    public function callback($provider){
        try {
            $user = Socialite::driver($provider)->user();
            $finduser = Member::where('email', $user->email)->first();
            if($finduser){
                Auth::guard('member')->login($finduser);
                return redirect()->route('account.profile');
            }else{
                $password = Str::random(8);
                $id = Member::insertGetId([
                    'first_name' => $user->name,
                    'last_name' => '',
                    'email' => $user->email,
                    'password' => bcrypt($password),
                    'status' => '1',
                    'type' => '1',
                    'provider' => $provider,
                    'provider_id' => $user->id,
                    'created_at' =>  date('Y-m-d H:i:s')
                ]);
                $data = array(
                    'name' =>$user->name,
                    'email' => $user->email,
                    'password' => $password,
                );
                $this->send('Website::email.social','Đăng ký thành viên',$user->email,$data);
                $member = Member::where('email', $user->email)->first();
                Auth::guard('member')->login($member);
                return redirect()->route('account.profile');;
            }
        } catch (Exception $e) {
            return redirect('/');
        }
    }

    // public function testSend(){
    //     $password = Str::random(8);
    //     $data = array(
    //         'name' => 'Xuân Trường',
    //         'email' => 'nxtruong7891nd@gmail.com',
    //         'password' => $password,
    //     );
    //     $this->send('Website::email.social','Đăng ký thành viên','onepieceworldnet0@gmail.com',$data);
    // }

    public function forgot(Request $request){
        $this->validate($request,[
            'email' => 'required',
        ],[
            'email.required' => 'Bạn chưa nhập địa chỉ email!',
        ]);
        $member = Member::where([['email',$request->email],['type','1']])->first();
        if(isset($member) && !empty($member)){
            $password = Str::random(10);
            $id = Member::where([['email',$request->email],['type','1']])->update(
                [
                    'password' => bcrypt($password),
                ]
            );
            if($id > 0){
                $data = array("email" => $request->email,'password' => $password);
                $this->send('Website::email.forgot','Mật khẩu mới',$request->email,$data);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Mật khẩu mới đã được gửi vào email '.$request->email.'. Bạn vào email để lấy thông tin đăng nhập.'
                ]);
            }else{
                return response()->json([
                    'status' => 'erorr',
                    'message' => 'Có lỗi xảy ra trong quá trình xử lý, xin vui lòng thử lại'
                ]);
            }
        }else{
            return response()->json([
                'status' => 'erorr',
                'message' => 'Địa chỉ email không tồn tại'
            ]);
        }
    }

    public function activation($token){
        $activation = $this->getActivationByToken($token);
        if ($activation === null) return redirect()->route('home');
        $member = Member::find($activation->member_id);
        $member->status = 1;
        $member->save();
        $this->deleteActivation($token);
        return view('Website::member.active');
    }

    public function loginGoogle(Request $request){
        $client = new  \Google_Client(['client_id' => env('GOOGLE_CLIENT_ID')]);
        $payload = $client->verifyIdToken($request->access_token);
        if (empty($payload)) {
            return response()->json([
                'status' => 400
            ]);
        }
        $finduser = Member::where('email', $payload['email'])->first();
        if($finduser){
            Auth::guard('member')->login($finduser);
            return response()->json([
                'status' => 200,
                'url' => route('account.profile')
            ]);
        }else{
            $password = Str::random(8);
            $id = Member::insertGetId([
                'first_name' => $payload['given_name'],
                'last_name' => $payload['family_name'],
                'email' => $payload['email'],
                'password' => bcrypt($password),
                'status' => '1',
                'type' => '1',
                'provider' => 'google',
                'provider_id' => $payload['sub'],
                'created_at' =>  date('Y-m-d H:i:s')
            ]);
            $data = array(
                'name' => $payload['given_name'],
                'email' => $payload['email'],
                'password' => $password,
            );
            $this->send('Website::email.social','Đăng ký thành viên',$payload['email'],$data);
            $member = Member::where('email', $payload['email'])->first();
            Auth::guard('member')->login($member);
            return response()->json([
                'status' => 200,
                'url' => route('account.profile')
            ]);
        }  
    }
}