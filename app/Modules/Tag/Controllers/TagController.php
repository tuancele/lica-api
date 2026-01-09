<?php

namespace App\Modules\Tag\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Tag\Models\Tag;
use Validator;
use Illuminate\Support\Facades\Auth;
class TagController extends Controller
{
    private $model;
    private $view = 'Tag';
    public function __construct(Tag $model){
        $this->model = $model;
        
    }
    public function index(Request $request)
    {
        active('product','tag');
        $data['tags'] = $this->model::where(function ($query) use ($request) {
            if($request->get('status') != "") {
                $query->where('status', $request->get('status'));
            }
            if($request->get('keyword') != "") {
                $query->where('name','like', $request->get('keyword').'%');
            }
        })->orderBy('id','desc')->paginate(10);
        return view($this->view.'::index',$data);
    }   
    public function create(){
        active('product','tag');
        return view($this->view.'::create');
    }
    public function store(Request $request)
    {   
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:250',
            'slug' => 'required|min:1|max:250|unique:tags,slug',
        ],[
            'name.required' => 'Tiêu đề không được bỏ trống.',
            'name.min' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'name.max' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'slug.required' => 'Bạn chưa nhập đường dẫn',
            'slug.min' => 'Đường dẫn có độ dài từ 1 đến 250 ký tự',
            'slug.max' => 'Đường dẫn có độ dài từ 1 đến 250 ký tự',
            'slug.unique' => 'Đường dẫn đã tồn tại',
        ]);
        if($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }
        $id = Tag::insertGetId(
            [
                'name' => $request->name,
                'slug' => $request->slug,
                'content' => $request->content,
                'status' => $request->status,
                'seo_title' => $request->seo_title,
                'seo_description' => $request->seo_description,
                'user_id'=> Auth::id(),
                'created_at' => date('Y-m-d H:i:s')
            ]
        );
        if($id > 0){
            return response()->json([
                'status' => 'success',
                'alert' => 'Thêm thành công!',
                'url' => '/admin/tag'
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'errors' => array('alert' => array('0' => 'Thêm không thành công!'))
            ]);
        }
    }
    public function edit($id){
        active('product','tag');
        $data['detail'] = Tag::find($id);
        return view($this->view.'::edit',$data);
    }
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:250',
            'slug' => 'required|min:1|max:250|unique:tags,slug,'.$request->id,
        ],[
            'name.required' => 'Tiêu đề không được bỏ trống.',
            'name.min' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'name.max' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'slug.required' => 'Bạn chưa nhập đường dẫn',
            'slug.min' => 'Đường dẫn có độ dài từ 1 đến 250 ký tự',
            'slug.max' => 'Đường dẫn có độ dài từ 1 đến 250 ký tự',
            'slug.unique' => 'Đường dẫn đã tồn tại',
        ]);
        if($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }
        Tag::where('id',$request->id)->update(array(
            'name' => $request->name,
            'slug' => $request->slug,
            'content' => $request->content,
            'status' => $request->status,
            'seo_title' => $request->seo_title,
            'seo_description' => $request->seo_description,
            'user_id'=> Auth::id()
        ));
        return response()->json([
            'status' => 'success',
            'alert' => 'Sửa thành công!',
            'url' => '/admin/tag'
        ]);
    }
    public function delete(Request $request)
    {
        $data = Tag::findOrFail($request->id)->delete();
        if($request->page !=""){
            $url = '/admin/tag?page='.$request->page;
        }else{
            $url = '/admin/tag';
        }
        return response()->json([
            'status' => 'success',
            'alert' => 'Xóa thành công!',
            'url' => '/admin/tag'
        ]);
    }
    public function status(Request $request){
        Tag::where('id',$request->id)->update(array(
            'status' => $request->status
        ));
        return response()->json([
            'status' => 'success',
            'alert' => 'Đổi trạng thái thành công!',
            'url' => '/admin/tag'
        ]);
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
                Tag::where('id',$value)->update(array(
                    'status' => '0'
                ));
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Ẩn thành công!',
                'url' => '/admin/tag'
            ]);
        }elseif($action == 1){
            foreach($check as $key => $value){
                Tag::where('id',$value)->update(array(
                    'status' => '1'
                ));
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Hiển thị thành công!',
                'url' => '/admin/tag'
            ]);
        }else{
            foreach($check as $key => $value){
                Tag::where('id',$value)->delete();
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => '/admin/tag'
            ]);
        }
    }
}
