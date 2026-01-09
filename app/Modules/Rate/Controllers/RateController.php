<?php

namespace App\Modules\Rate\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Rate\Models\Rate;
use App\Modules\Product\Models\Product;
use Validator;
use Illuminate\Support\Facades\Auth;
use Session;
class RateController extends Controller
{
    private $model;
    private $controller = 'rate';
    private $view = 'Rate';
    public function __construct(Rate $model){
        $this->model = $model;
    }
    public function index(Request $request)
    {
        active('product','rate');
        $data['list'] = $this->model::where(function ($query) use ($request) {
            if($request->get('status') != "") {
	            $query->where('status', $request->get('status'));
            }
            if($request->get('product_id') != "") {
                $query->where('product_id', $request->get('product_id'));
            }
            if($request->get('keyword') != "") {
	            $query->where('name','like','%'.$request->get('keyword').'%');
	        }
        })->orderBy('created_at','desc')->paginate(10);
        $data['products'] = Product::select('id','name')->where([['status','1'],['type','product']])->orderBy('name','asc')->get();
        return view($this->view.'::index',$data);
    }
    public function create(){
        active('product','rate');
        $data['products'] = Product::select('id','name')->where([['status','1'],['type','product']])->orderBy('name','asc')->get();
        return view($this->view.'::create',$data);
    }
    public function edit($id){
        active('product','rate');
        $detail = $this->model::find($id);
        if(!isset($detail) && empty($detail)){
            return redirect('admin/rate');
        }
        $data['products'] = Product::select('id','name')->where([['status','1'],['type','product']])->orderBy('name','asc')->get();
        $data['detail'] = $detail;
        $data['gallerys'] = json_decode($detail->images);
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
            'rate' => $request->rate,
            'content' => $request->content,
            'product_id' => $request->product_id,
            'email' => $request->email,
            'title' => $request->title,
            'phone' => $request->phone,
            'images' => json_encode($request->imageOther),
            'status' => $request->status,
            'user_id'=> Auth::id()
        ));
        if($up > 0){
            return response()->json([
                'status' => 'success',
                'alert' => 'Sửa thành công!',
                'url' => '/admin/'.$this->controller
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
                'rate' => $request->rate,
                'content' => $request->content,
                'product_id' => $request->product_id,
                'email' => $request->email,
                'phone' => $request->phone,
                'title' => $request->title,
                'status' => $request->status,
                'images' => json_encode($request->imageOther),
                'user_id'=> Auth::id(),
                'created_at' => date('Y-m-d H:i:s')
            ]
        );
        if($id > 0){
            return response()->json([
                'status' => 'success',
                'alert' => 'Thêm thành công!',
                'url' => '/admin/'.$this->controller
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
            $url = '/admin/'.$this->controller.'?page='.$request->page;
        }else{
            $url = '/admin/'.$this->controller;
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
            'url' => '/admin/'.$this->controller
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
                'url' => '/admin/'.$this->controller
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
                'url' => '/admin/'.$this->controller
            ]);
        }else{
            foreach($check as $key => $value){
                $this->model::where('id',$value)->delete();
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => '/admin/'.$this->controller
            ]);
        }
    }

    public function upload(Request $request){
        $validatedData = $request->validate([
            'files' => 'required',
            'files.*' => 'mimes:jpeg,png,jpg,gif,webp'
        ]);
        if($request->TotalFiles > 0)
        {
           for ($x = 0; $x < $request->TotalFiles; $x++)
           {
               if ($request->hasFile('files'.$x))
                {
                    $file      = $request->file('files'.$x);
                    $name = $file->getClientOriginalName();
                    $path = '/uploads/images/reviews/';
                    $file->move('uploads/images/reviews', $name);
                    $insert[$x] = $path.$name;
                }
           }
            return response()->json($insert);
        }
        else
        {
           return response()->json(["message" => "Xin vui lòng thử lại."]);
        }
    }
}
