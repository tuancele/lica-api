<?php

declare(strict_types=1);
namespace App\Modules\Post\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Post\Models\Post;
use Illuminate\Support\Facades\Auth;
use Validator;

class PostController extends Controller
{
    private $model;
    private $view = 'Post';
    public function __construct(Post $model){
        $this->model = $model;
    }
    public function index(Request $request)
    {
        //$this->authorize('post');
        active('post','list');
        $data['posts'] = $this->model::where('type','post')->where(function ($query) use ($request) {
            if($request->get('status') != "") {
	            $query->where('status', $request->get('status'));
            }
            if($request->get('cat_id') != "") {
	            $query->where('cat_id', $request->get('cat_id'));
            }
            if($request->get('keyword') != "") {
	            $query->where('name','like','%'.$request->get('keyword').'%');
	        }
        })->orderBy('created_at','desc')->paginate(10)->appends(['cat_id' => $request->get('cat_id'),'keyword' => $request->get('keyword'),'status' => $request->get('status')]);
        $data['categories'] = $this->model::where([['type','category'],['status','1']])->orderBy('sort','asc')->get();
        return view($this->view.'::index',$data);
    }
    public function create(){
        //$this->authorize('post-create');
        $data['categories'] = $this->model::where([['type','category'],['status','1']])->orderBy('sort','asc')->get();
        return view($this->view.'::create',$data);
    }
    public function edit($id){
        $post = $this->model::find($id);
        //$this->authorize($post,'post-edit');
        if(!isset($post) && empty($post)){
            return redirect()->route('post');
        }
        $data['detail'] = $post;
        $data['categories'] = $this->model::where([['type','category'],['status','1']])->orderBy('sort','asc')->get();
        return view($this->view.'::edit',$data);
    }
    public function update(Request $request)
    {
        //$post = $this->model::find($request->id);
        //$this->authorize($post,'post-edit');
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:250',
            'slug' => 'required|min:1|max:250|unique:posts,slug,'.$request->id,
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
        $this->model::where('id',$request->id)->update(array(
            'name' => $request->name,
            'slug' => $request->slug,
            'image' => $request->image,
            'description' => $request->description,
            'content' => $request->content,
            'status' => $request->status,
            'type' => 'post',
            'cat_id' => $request->cat_id,
            'seo_title' => $request->seo_title,
            'seo_description' => $request->seo_description,
            'user_id'=> Auth::id()
        ));
        return response()->json([
            'status' => 'success',
            'alert' => 'Sửa thành công!',
            'url' => route('post')
        ]);
    }

    public function store(Request $request)
    {   
        //$this->authorize('post-create');
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:250',
            'slug' => 'required|min:1|max:250|unique:posts,slug',
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
        $id = $this->model::insertGetId(
            [
                'name' => $request->name,
                'slug' => $request->slug,
                'image' => $request->image,
                'description' => $request->description,
                'content' => $request->content,
                'status' => $request->status,
                'type' => 'post',
                'cat_id' => $request->cat_id,
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
                'url' => route('post')
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
        //$post =  $this->model::find($request->id);
        //$this->authorize($post,'post-delete');
        $data = $this->model::findOrFail($request->id)->delete();
        if($request->page !=""){
            $url = route('post').'?page='.$request->page;
        }else{
            $url = route('post');
        }
        return response()->json([
            'status' => 'success',
            'alert' => 'Xóa thành công!',
            'url' => $url
        ]);
    }
    public function status(Request $request){
       // $post =  $this->model::find($request->id);
        //$this->authorize($post,'post-edit');
        $this->model::where('id',$request->id)->update(array(
            'status' => $request->status
        ));
        return response()->json([
            'status' => 'success',
            'alert' => 'Đổi trạng thái thành công!',
            'url' => route('post')
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
                //$post =  $this->model::find($value);
                //$this->authorize($post,'post-edit');
                $this->model::where('id',$value)->update(array(
                    'status' => '0'
                ));
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Ẩn thành công!',
                'url' => route('post')
            ]);
        }elseif($action == 1){
            foreach($check as $key => $value){
                //$post =  $this->model::find($value);
                //$this->authorize($post,'post-edit');
                $this->model::where('id',$value)->update(array(
                    'status' => '1'
                ));
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Hiển thị thành công!',
                'url' => route('post')
            ]);
        }else{
            foreach($check as $key => $value){
                //$post =  $this->model::find($value);
                //$this->authorize($post,'post-delete');
                $this->model::where('id',$value)->delete();
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => route('post')
            ]);
        }
    }
}
