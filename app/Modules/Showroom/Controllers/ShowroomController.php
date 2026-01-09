<?php

namespace App\Modules\Showroom\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Showroom\Models\Showroom;
use App\Modules\GroupShowroom\Models\GroupShowroom;
use Validator;
use Illuminate\Support\Facades\Auth;
use Session;
class ShowroomController extends Controller
{
    private $model;
    private $controller = 'showroom';
    private $view = 'Showroom';
    public function __construct(Showroom $model){
        $this->model = $model;
    }
    public function index(Request $request)
    {
        active('showroom','list');
        $data['list'] = $this->model::where(function ($query) use ($request) {
            if($request->get('status') != "") {
	            $query->where('status', $request->get('status'));
            }
            if($request->get('cat_id') != "") {
                $query->where('cat_id', $request->get('cat_id'));
            }
            if($request->get('keyword') != "") {
	            $query->where('name','like','%'.$request->get('keyword').'%');
	        }
        })->orderBy('sort','asc')->paginate(10)->appends(['keyword' => $request->get('keyword'),'status' => $request->get('status'),'cat_id' => $request->get('cat_id')]);
        $data['categories'] = GroupShowroom::where('status','1')->orderBy('sort','asc')->get();
        return view($this->view.'::index',$data);
    }
    public function create(){
        active('showroom','list');
        $data['categories'] = GroupShowroom::where('status','1')->orderBy('sort','asc')->get();
        return view($this->view.'::create',$data);
    }
    public function edit($id){
        active('showroom','list');
        $data['categories'] = GroupShowroom::where('status','1')->orderBy('sort','asc')->get();
        $data['detail'] = $this->model::find($id);
        return view($this->view.'::edit',$data);
    }
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:250',
        ],[
            'name.required' => 'Tiêu đề không được bỏ trống.',
            'name.min' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'name.max' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
        ]);
        if($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }
        $up = $this->model::where('id',$request->id)->update(array(
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
            'cat_id' => $request->cat_id,
            'map' => $request->map,
            'status' => $request->status,
            'user_id'=> Auth::id()
        ));
        if($up > 0){
            return response()->json([
                'status' => 'success',
                'alert' => 'Sửa thành công!',
                'url' => '/admin/showroom'
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
        ],[
            'name.required' => 'Tiêu đề không được bỏ trống.',
            'name.min' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'name.max' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
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
                'phone' => $request->phone,
                'address' => $request->address,
                'cat_id' => $request->cat_id,
                'map' => $request->map,
                'status' => $request->status,
                'user_id'=> Auth::id(),
                'created_at' => date('Y-m-d H:i:s')
            ]
        );
        if($id > 0){
            return response()->json([
                'status' => 'success',
                'alert' => 'Thêm thành công!',
                'url' => '/admin/showroom'
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
            $url = '/admin/showroom?page='.$request->page;
        }else{
            $url = '/admin/showroom';
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
            'url' => '/admin/showroom'
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
                'alert' => 'Ẩn thành công!',
                'url' => '/admin/showroom'
            ]);
        }elseif($action == 1){
            foreach($check as $key => $value){
                $this->model::where('id',$value)->update(array(
                    'status' => '1'
                ));
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Hiển thị thành công!',
                'url' => '/admin/showroom'
            ]);
        }else{
            foreach($check as $key => $value){
                $this->model::where('id',$value)->delete();
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => '/admin/showroom'
            ]);
        }
    }
}
