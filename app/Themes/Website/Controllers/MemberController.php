<?php

namespace App\Themes\website\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Member\Models\Member;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderDetail;
use Illuminate\Support\Facades\Auth;
use App\Rules\MatchOldPassword;
use Illuminate\Support\Facades\Hash;
use App\Traits\Location;
use App\Modules\Address\Models\Address;
use App\Themes\Website\Models\MemberPromotion;
use Validator;

class MemberController extends Controller
{
	use Location;

	public function district($id){
        echo $this->getDistrict($id);
    }

    public function ward($id){
        echo $this->getWard($id);
    }

    public function profile(){
        $member = auth()->guard('member')->user();
        $detail = Member::find($member['id']);
		return view('Website::member.profile');
	}

	public function update(Request $request){
		if(getConfig('recaptcha_status')){
        	$context = stream_context_create([
	            'http' => [
	                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
	                'method' => 'POST',
	                'content' => http_build_query([
	                    'secret' => getConfig('recaptcha_secret_key'),
	                    'response' => request('recaptcha')
	                ])
	            ]
	        ]);
	        $result = json_decode(file_get_contents(getConfig('recaptcha_google'), false, $context));
	        if($result->success != true) return redirect()->back()->with('error', 'Quá thời gian xử lý, xin vui lòng thử lại');
        }
		$member = auth()->guard('member')->user();
		$this->validate($request,[
            'first_name' => 'required',
            'last_name' => 'required',
            'phone' => 'required',
            'email' => 'required|unique:members,email,'.$member['id'],
        ],[
            'first_name.required' => 'Bạn chưa nhập họ',
            'last_name.required' => 'Bạn chưa nhập tên',
            'phone.required' => 'Bạn chưa nhập số điện thoại',
            'phone.unique' => 'Số điện thoại đã tồn tại',
            'email.required' => 'Bạn chưa nhập địa chỉ email',
            'email.unique' => 'Địa chỉ email đã tồn tại',
        ]);
        $update = Member::where('id',$member['id'])->update(array(
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'email' => $request->email
        ));
        if($update > 0){
        	return redirect()->route('account.profile');
        }else{
        	return redirect()->back()->with('status', 'Cập nhật thông tin tài khoản không thành công, xin vui lòng thử lại');
        }
	}

	public function address(){
		$member = auth()->guard('member')->user();
		$data['addresses'] = Address::where('member_id',$member['id'])->orderBy('is_default','desc')->get();
		$data['province'] = $this->getProvince();
		return view('Website::member.address',$data);
	}

	public function logout(){
	 	Auth::guard('member')->logout();
        return redirect('/');
	}

	public function storeAddress(Request $request){
		if(getConfig('recaptcha_status')){
        	$context = stream_context_create([
	            'http' => [
	                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
	                'method' => 'POST',
	                'content' => http_build_query([
	                    'secret' => getConfig('recaptcha_secret_key'),
	                    'response' => request('recaptcha')
	                ])
	            ]
	        ]);
	        $result = json_decode(file_get_contents(getConfig('recaptcha_google'), false, $context));
	        if($result->success != true) return redirect()->back()->with('error', 'Quá thời gian xử lý, xin vui lòng thử lại');
        }
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'phone' => 'required',
            'address' => 'required',
            'provinceid' => 'required',
            'districtid' => 'required',
            'wardid' => 'required',
        ],[
            'first_name.required' => 'Bạn chưa nhập họ',
            'last_name.required' => 'Bạn chưa nhập tên',
            'phone.required' => 'Bạn chưa nhập số điện thoại',
            'address.required' => 'Bạn chưa nhập địa chỉ',
            'provinceid.required' => 'Bạn chưa chọn tỉnh/thành phố',
            'districtid.required' => 'Bạn chưa chọn quận/huyện',
            'wardid.required' => 'Bạn chưa chọn phường/xã'
        ]);
        if($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }
        $member = auth()->guard('member')->user();
        $id = Address::insertGetId(
            [
            	'member_id' => $member['id'],
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'wardid' => $request->wardid,
                'districtid' => $request->districtid,
                'provinceid'=>$request->provinceid,
                'created_at' => date('Y-m-d H:i:s')
            ]
        );
        if($id > 0){
        	if(isset($request->default) && $request->default == 1){
        		Address::where([['member_id',$member['id']],['is_default','1']])->update(['is_default' => '0']);
        		Address::where([['member_id',$member['id']],['id',$id]])->update(['is_default' => '1']);
        	}
            return response()->json([
                'status' => 'success',
                'message' => 'Thêm địa chỉ thành công',
                'url' => route('account.address')
            ]);
        }else{
            return response()->json([
                'status' => 'erorr',
                'message' => 'Thêm địa chỉ không thành công, xin vui lòng thử lại'
            ]);
        }
	}

	public function editAddress(Request $request){
		$member = auth()->guard('member')->user();
		$detail = Address::where([['id',$request->id],['member_id',$member['id']]])->first();
		if(!isset($detail) && empty($detail)){

		}
		$data['detail'] = $detail;
		$data['province'] = $this->getProvince($detail->provinceid);
		$data['district'] = $this->getDistrict($detail->provinceid,$detail->districtid);
		$data['ward'] = $this->getWard($detail->districtid,$detail->wardid);
		return view('Website::member.editaddress',$data);
	}

	public function updateAddress(Request $request){
		if(getConfig('recaptcha_status')){
        	$context = stream_context_create([
	            'http' => [
	                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
	                'method' => 'POST',
	                'content' => http_build_query([
	                    'secret' => getConfig('recaptcha_secret_key'),
	                    'response' => request('recaptcha')
	                ])
	            ]
	        ]);
	        $result = json_decode(file_get_contents(getConfig('recaptcha_google'), false, $context));
	        if($result->success != true) return redirect()->back()->with('error', 'Quá thời gian xử lý, xin vui lòng thử lại');
        }
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'phone' => 'required',
            'address' => 'required',
            'provinceid' => 'required',
            'districtid' => 'required',
            'wardid' => 'required',
        ],[
            'first_name.required' => 'Bạn chưa nhập họ',
            'last_name.required' => 'Bạn chưa nhập tên',
            'phone.required' => 'Bạn chưa nhập số điện thoại',
            'address.required' => 'Bạn chưa nhập địa chỉ',
            'provinceid.required' => 'Bạn chưa chọn tỉnh/thành phố',
            'districtid.required' => 'Bạn chưa chọn quận/huyện',
            'wardid.required' => 'Bạn chưa chọn phường/xã'
        ]);
        if($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }
        $member = auth()->guard('member')->user();
        $update = Address::where([['id',$request->id],['member_id',$member['id']]])->update(
            [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'wardid' => $request->wardid,
                'districtid' => $request->districtid,
                'provinceid'=>$request->provinceid,
                'created_at' => date('Y-m-d H:i:s')
            ]
        );
        if($update > 0){
        	if(isset($request->default) && $request->default == 1){
        		Address::where([['member_id',$member['id']],['is_default','1']])->update(['is_default' => '0']);
        		Address::where([['member_id',$member['id']],['id',$request->id]])->update(['is_default' => '1']);
        	}
            return response()->json([
                'status' => 'success',
                'message' => 'Chỉnh sửa địa chỉ thành công',
                'url' => route('account.address')
            ]);
        }else{
            return response()->json([
                'status' => 'erorr',
                'message' => 'Chỉnh sửa địa chỉ không thành công, xin vui lòng thử lại'
            ]);
        }
	}

	public function deleteAddress(Request $request){
		try{
			$member = auth()->guard('member')->user();
			Address::where([['id',$request->id],['member_id',$member['id']]])->delete();
			return response()->json([
                'status' => 'success'
            ]);
		}catch (Exception $e) {
            return response()->json([
                'status' => 'erorr',
                'message' => 'Có lỗi xảy ra, xin vui lòng thử lại'
            ]);
        }
	}

    public function password(){
        return view('Website::member.password');
    }

    public function updatePassword(Request $request){
        try{
            if(getConfig('recaptcha_status')){
                $context = stream_context_create([
                    'http' => [
                        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                        'method' => 'POST',
                        'content' => http_build_query([
                            'secret' => getConfig('recaptcha_secret_key'),
                            'response' => request('recaptcha')
                        ])
                    ]
                ]);
                $result = json_decode(file_get_contents(env('GOOGLEURL'), false, $context));
                if($result->success != true) return redirect()->back()->with('error', 'Quá thời gian xử lý, xin vui lòng thử lại');
            }
            $this->validate($request,[
                'password_current' => ['required', new MatchOldPassword],
                'password_new' => 'required|min:6',
                'confirm' => 'required|same:password_new'
            ],[
                'password_current.required' => 'Mật khẩu hiện tại không bỏ trống!',
                'password_new.required' => 'Mật khẩu mới không bỏ trống!',
                'password_new.min' => 'Mật khẩu tối thiểu 6 ký tự',
                'confirm.required' => 'Bạn chưa nhập mật khẩu!',
                'confirm.same' => 'Nhập lại mật khẩu không đúng!'
            ]);
            $member = auth()->guard('member')->user();
            Member::find($member['id'])->update(['password'=> Hash::make($request->password_new)]);
            return redirect()->route('account.password')->with('success', 'Đổi mật khẩu thành công!');
        }catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function orders(){
        $member = auth()->guard('member')->user();
        $data['orders'] = Order::where('member_id',$member['id'])->paginate(10);
        return view('Website::member.orders',$data);
    }

    public function order($code){
        $member = auth()->guard('member')->user();
        $order = Order::where([['member_id',$member['id']],['code',$code]])->first();
        if(empty($order)){
            return redirect()->route('account.orders');
        }
        $data['detail'] = $order;
        return view('Website::member.order',$data);
    }

    public function addPromotion(Request $request){
        $member = auth()->guard('member')->user();
        $check = MemberPromotion::where([['member_id',$member['id']],['promotion_id', $request->id]])->get();
        if($check->count() > 0){
            return response()->json([
                'status' => 'error',
                'message' => 'Bạn đã lưu mã giảm giá này rồi!'
            ]);
        }else{
            $id = MemberPromotion::insertGetId(
                [
                    'member_id' => $member['id'],
                    'promotion_id' => $request->id,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            );
            if($id > 0){
                return response()->json([
                    'status' => 'success'
                ]);
            }else{
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bạn đã lưu mã giảm giá này rồi!'
                ]);
            }
        }
    }

    public function promotion(){
        $member = auth()->guard('member')->user();
        $data['list'] = MemberPromotion::where('member_id',$member['id'])->latest()->get();
        return view('Website::member.promotion',$data);
    }
}