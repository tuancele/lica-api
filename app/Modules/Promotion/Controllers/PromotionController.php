<?php

namespace App\Modules\Promotion\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Promotion\Models\Promotion;
use Validator;
use Illuminate\Support\Facades\Auth;
use Session;
class PromotionController extends Controller
{
    private $model;
    private $controller = 'promotion';
    private $view = 'Promotion';
    public function __construct(Promotion $model){
        $this->model = $model;
    }
    public function index(Request $request)
    {
        active('product','promotion');
        $data['list'] = $this->model::where(function ($query) use ($request) {
            if($request->get('status') != "") {
	            $query->where('status', $request->get('status'));
            }
            if($request->get('keyword') != "") {
	            $query->where('name','like','%'.$request->get('keyword').'%');
	        }
        })->latest()->paginate(10)->appends($request->query());
        return view($this->view.'::index',$data);
    }
    public function create(){
        active('product','promotion');
        return view($this->view.'::create');
    }
    public function edit($id){
        active('product','promotion');
        $detail = $this->model::find($id);
        if(!isset($detail) && empty($detail)){
            return redirect()->route('promotion.index');
        }
        $data['detail'] = $detail;
        return view($this->view.'::edit',$data);
    }
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:250',
            'code' => 'required|unique:promotions,code,'.$request->id,
            'number' => 'required',
            'value' => 'required',
        ],[
            'name.required' => 'Tiêu đề không được bỏ trống.',
            'name.min' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'name.max' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'code.required' => 'Mã giảm giá không được bỏ trống.',
            'code.unique' => 'Mã giảm giá đã tồn tại',
            'number.required' => 'Số lượng dùng không được bỏ trống.',
            'value.required' => 'Giá trị giảm không được bỏ trống.',
        ]);
        if($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }
        $up = $this->model::where('id',$request->id)->update(array(
            'code' => $request->code,
            'name' => $request->name,
            'value' => $request->value,
            'unit' => $request->unit,
            'number' => $request->number,
            'start' => $request->start,
            'end' => $request->end,
            'status' => $request->status,
            'endow' => $request->endow,
            'order_sale' => $request->order_sale,
            'payment' => $request->payment,
            'content' => $request->content,
            'user_id'=> Auth::id()
        ));
        if($up > 0){
            return response()->json([
                'status' => 'success',
                'alert' => 'Sửa thành công!',
                'url' => route('promotion.index')
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'errors' => array('alert' => array('0' => 'Sửa không thành công!'))
            ]);
        }
    }

    public function store(Request $request)
    {   
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:250',
            'code' => 'required|unique:promotions,code',
            'number' => 'required',
            'value' => 'required',
        ],[
            'name.required' => 'Tiêu đề không được bỏ trống.',
            'name.min' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'name.max' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'code.required' => 'Mã giảm giá không được bỏ trống.',
            'code.unique' => 'Mã giảm giá đã tồn tại',
            'number.required' => 'Số lượng dùng không được bỏ trống.',
            'value.required' => 'Giá trị giảm không được bỏ trống.',
        ]);
        if($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }
        $id = $this->model::insertGetId(
            [
                'name' => $request->name,
                'code' => $request->code,
                'value' => $request->value,
                'unit' => $request->unit,
                'number' => $request->number,
                'start' => $request->start,
                'end' => $request->end,
                'status' => $request->status,
                'endow' => $request->endow,
                'order_sale' => $request->order_sale,
                'payment' => $request->payment,
                'content' => $request->content,
                'user_id'=> Auth::id(),
                'created_at' => date('Y-m-d H:i:s')
            ]
        );
        if($id > 0){
            return response()->json([
                'status' => 'success',
                'alert' => 'Thêm thành công!',
                'url' => route('promotion.index')
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'errors' => array('alert' => array('0' => 'Thêm không thành công!'))
            ]);
        }
    }
    public function delete(Request $request)
    {
        $data = $this->model::findOrFail($request->id)->delete();
        if($request->page !=""){
            $url = route('promotion.index').'?page='.$request->page;
        }else{
            $url = route('promotion.index');
        }
        return response()->json([
            'status' => 'success',
            'alert' => 'Xóa thành công!',
            'url' => $url
        ]);
    }
    public function status(Request $request){
        $this->model::where('id',$request->id)->update(array(
            'status' => $request->status
        ));
        return response()->json([
            'status' => 'success',
            'alert' => 'Đổi trạng thái thành công!',
            'url' => route('promotion.index')
        ]);
    }
    public function sort(Request $req){
        $sort = $req->sort;
        if(isset($sort) && !empty($sort)){
            foreach ($sort as $key => $value) {
                $this->model::where('id',$key)->update(array(
                    'sort' => $value
                ));
            }
        }
    }
    public function action(Request $request){
        $check = $request->checklist;
        if(!isset($check) && empty($check)){
            return response()->json([
                'status' => 'error',
                'errors' => array('alert' => array('0' => 'Chưa chọn dữ liệu cần thao tác!'))
            ]);
        }
        $action = $request->action;
        if($action == 0){
            foreach($check as $key => $value){
                $this->model::where('id',$value)->update(array(
                    'status' => '0'
                ));
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Thay đổi trạng thái thành công!',
                'url' => route('promotion.index')
            ]);
        }elseif($action == 1){
            foreach($check as $key => $value){
                $this->model::where('id',$value)->update(array(
                    'status' => '1'
                ));
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Thay đổi trạng thái thành công!',
                'url' => route('promotion.index')
            ]);
        }else{
            foreach($check as $key => $value){
                $this->model::where('id',$value)->delete();
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => route('promotion.index')
            ]);
        }
    }
}
