<?php

declare(strict_types=1);
namespace App\Modules\Member\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Member\Models\Member;
use Validator;
use Illuminate\Support\Facades\Auth;
use App\Traits\Location;
use App\Modules\Order\Models\Order;
use App\Modules\Address\Models\Address;

class MemberController extends Controller
{
    use Location;

    private $model;
    private $controller = 'member';
    private $view = 'Member';

    public function __construct(Member $model)
    {
        $this->model = $model;
    }

    public function district($id)
    {
        echo $this->getDistrict($id);
    }

    public function ward($id)
    {
        echo $this->getWard($id);
    }

    public function index(Request $request)
    {
        active('member', 'member');
        $query = $this->model::query();

        if ($request->get('status') != "") {
            $query->where('status', $request->get('status'));
        }
        if ($request->get('keyword') != "") {
            $keyword = $request->get('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('first_name', 'like', '%' . $keyword . '%')
                  ->orWhere('last_name', 'like', '%' . $keyword . '%')
                  ->orWhere('email', 'like', '%' . $keyword . '%')
                  ->orWhere('phone', 'like', '%' . $keyword . '%');
            });
        }

        $data['list'] = $query->orderBy('id', 'desc')->paginate(20)->appends([
            'keyword' => $request->get('keyword'),
            'status' => $request->get('status')
        ]);

        return view($this->view . '::index', $data);
    }

    public function create()
    {
        active('member', 'member');
        $data['province'] = $this->getProvince();
        return view($this->view . '::create', $data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|unique:members,email',
            'phone' => 'required',
        ], [
            'first_name.required' => 'Họ không được bỏ trống.',
            'last_name.required' => 'Tên không được bỏ trống',
            'email.required' => 'Địa chỉ email không được bỏ trống',
            'email.unique' => 'Địa chỉ email đã tồn tại',
            'phone.required' => 'Số điện thoại không được bỏ trống',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        $id = Member::insertGetId([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'status' => $request->status,
            'password' => bcrypt($request->password),
            'type' => '1',
            'user_id' => Auth::id(),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        if ($id > 0) {
            Address::insert([
                'member_id' => $id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'wardid' => $request->wardid,
                'districtid' => $request->districtid,
                'provinceid' => $request->provinceid,
                'address' => $request->address,
                'email' => $request->email,
                'is_default' => '1',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            return response()->json([
                'status' => 'success',
                'alert' => 'Thêm thành công!',
                'url' => route('member')
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Thêm không thành công!'
            ]);
        }
    }

    public function view($id)
    {
        active('member', 'member');
        $member = Member::find($id);
        if (!$member) {
            return redirect()->route('member');
        }
        $data['member'] = $member;
        $data['orders'] = $member->order;
        $data['order'] = Order::select('id', 'code', 'created_at')->where('member_id', $id)->orderBy('created_at', 'desc')->first();
        $data['income'] = Order::select('total', 'fee_ship', 'sale')->where([['member_id', $id], ['payment', '1'], ['ship', '2'], ['status', '1']])->get();
        $data['addresss'] = $member->address;
        return view($this->view . '::view', $data);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|min:1|max:250',
            'last_name' => 'required|min:1|max:250',
            'email' => 'required|unique:members,email,' . $request->id,
            'phone' => 'required',
        ], [
            'first_name.required' => 'Họ không được bỏ trống.',
            'last_name.required' => 'Tên không được bỏ trống.',
            'email.required' => 'Địa chỉ email không được bỏ trống',
            'email.unique' => 'Địa chỉ email đã tồn tại',
            'phone.required' => 'Số điện thoại không được bỏ trống',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        $update = Member::where('id', $request->id)->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'birthday' => ($request->birthday != "") ? date('Y-m-d', strtotime($request->birthday)) : null,
            'status' => $request->status,
            'type' => '1',
            'user_id' => Auth::id()
        ]);

        if ($update > 0) {
            return response()->json([
                'status' => 'success',
                'alert' => 'Cập nhật thành công!'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Cập nhật không thành công!'
            ]);
        }
    }

    public function get_addaddress(Request $req)
    {
        $data['id'] = $req->id;
        $data['province'] = $this->getProvince();
        return view($this->view . '::add_address', $data);
    }

    public function addAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'phone' => 'required',
            'address' => 'required',
        ], [
            'first_name.required' => 'Họ không được bỏ trống.',
            'last_name.required' => 'Tên không được bỏ trống.',
            'phone.required' => 'Số điện thoại không được bỏ trống',
            'address.required' => 'Địa chỉ không được bỏ trống',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        $id = Address::insertGetId([
            'member_id' => $request->member_id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'wardid' => $request->wardid,
            'districtid' => $request->districtid,
            'provinceid' => $request->provinceid,
            'is_default' => '0',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        if ($id > 0) {
            return response()->json([
                'status' => 'success',
                'data' => $this->loadAddress($request->member_id)
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Tạo địa chỉ mới không thành công!'
            ]);
        }
    }

    public function delAddress(Request $req)
    {
        $del = Address::where('id', $req->id)->delete();
        if ($del > 0) {
            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Xóa địa chỉ không thành công!'
            ]);
        }
    }

    public function get_editaddress(Request $req)
    {
        $address = Address::find($req->id);
        if (!$address) {
            return 'Không tìm thấy địa chỉ này';
        }
        $data['detail'] = $address;
        $data['province'] = $this->getProvince($address->provinceid);
        $data['district'] = $this->getDistrict($address->provinceid, $address->districtid);
        $data['ward'] = $this->getWard($address->districtid, $address->wardid);
        return view($this->view . '::address', $data);
    }

    public function editAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'phone' => 'required',
            'address' => 'required',
        ], [
            'first_name.required' => 'Họ không được bỏ trống.',
            'last_name.required' => 'Tên không được bỏ trống.',
            'phone.required' => 'Số điện thoại không được bỏ trống',
            'address.required' => 'Địa chỉ không được bỏ trống',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        $update = Address::where('id', $request->id)->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'wardid' => $request->wardid,
            'districtid' => $request->districtid,
            'provinceid' => $request->provinceid,
        ]);

        if ($update > 0) {
            return response()->json([
                'status' => 'success',
                'data' => $this->loadAddress($request->member_id)
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Sửa địa chỉ không thành công!'
            ]);
        }
    }

    public function editPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required',
            'confirm' => 'required|same:password',
        ], [
            'password.required' => 'Mật khẩu không được bỏ trống.',
            'confirm.required' => 'Xác nhận mật khẩu không được bỏ trống',
            'confirm.same' => 'Mật khẩu xác nhận không khớp',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        $update = Member::where('id', $request->id)->update([
            'password' => bcrypt($request->password),
        ]);

        if ($update > 0) {
            return response()->json([
                'status' => 'success',
                'alert' => 'Thiết lập mật khẩu thành công!'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Cập nhật không thành công!'
            ]);
        }
    }

    public function loadAddress($id)
    {
        $addresss = Address::where('member_id', $id)->get();
        $html = "";
        if ($addresss->count() > 0) {
            foreach ($addresss as $address) {
                $html .= '<tr>
                   <td width="20%">' . $address->first_name . ' ' . $address->last_name . '</td>
                   <td width="15%">' . $address->phone . '</td>';
                $html .= '<td width="50%">';
                $province = ($address->province) ? ', ' . $address->province->name : '';
                $district = ($address->district) ? ', ' . $address->district->name : '';
                $ward = ($address->ward) ? $address->ward->name : '';
                $html .= '<p>' . $address->address . '</p>';
                $html .= '<p>' . $ward . $district . $province . '</p>';
                $html .= '</td>';
                $html .= '<td width="10%">
                     <a class="btn btn-primary btn-xs btn_edit" title="Sửa" data-id="' . $address->id . '"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>
                    <a class="btn btn-danger btn-xs del_address" title="Xóa" data-id="' . $address->id . '"><i class="fa fa-trash-o" aria-hidden="true"></i></a>
                   </td>
                 </tr>';
            }
        }
        return $html;
    }

    public function delete(Request $request)
    {
        $check = Order::select('id')->where('member_id', $request->id)->exists();
        if ($check) {
            return response()->json([
                'status' => 'error',
                'message' => 'Khách hàng đã có đơn hàng, không thể xóa!'
            ]);
        } else {
            Member::findOrFail($request->id)->delete();
            $url = ($request->page != "") ? '/admin/member?page=' . $request->page : '/admin/member';
            
            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => $url
            ]);
        }
    }

    public function action(Request $request)
    {
        $check = $request->checklist;
        if (empty($check)) {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Chưa chọn dữ liệu cần thao tác!']]
            ]);
        }
        $action = $request->action;
        if ($action == 2) {
            $total = 0;
            foreach ($check as $key => $value) {
                if (!Order::where('member_id', $value)->exists()) {
                    Member::where('id', $value)->delete();
                    $total++;
                }
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Đã xóa thành công ' . $total . ' khách hàng!',
                'url' => '/admin/member'
            ]);
        }
    }
}
