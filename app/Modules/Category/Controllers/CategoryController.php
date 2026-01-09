<?php

namespace App\Modules\Category\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Category\Models\Category;
use Validator;
use Illuminate\Support\Facades\Auth;
class CategoryController extends Controller
{
    public function index(Request $request)
    {
        active('post','category');
        $data['categories'] = Category::where([['type','category']])->where(function ($query) use ($request) {
            if($request->get('status') != "") {
                $query->where('status', $request->get('status'));
            }
            if($request->get('keyword') != "") {
                $query->where('name','like','%'.$request->get('keyword').'%');
            }
        })->orderBy('sort','asc')->get();
        return view('Category::index',$data);
    }
    public function create(){
        active('post','category');
        $data['categories'] = Category::where([['type','category'],['status','1']])->orderBy('sort','asc')->get();
        return view('Category::create',$data);
    }
    public function store(Request $request)
    {
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
        $id = Category::insertGetId(
            [
                'name' => $request->name,
                'slug' => $request->slug,
                'image' => $request->image,
                'description' => $request->description,
                'content' => $request->content,
                'status' => $request->status,
                'feature' => $request->feature,
                'type' => 'category',
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
                'url' => route('category')
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'errors' => array('alert' => array('0' => 'Thêm không thành công!'))
            ]);
        }
    }
    public function edit($id){
        active('post','category');
        $data['categories'] = Category::where([['type','category'],['status','1'],['id','!=',$id]])->orderBy('sort','asc')->get();
        $data['detail'] = Category::find($id);
        return view('Category::edit',$data);
    }
    public function update(Request $request)
    {
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
        Category::where('id',$request->id)->update(array(
            'name' => $request->name,
            'slug' => $request->slug,
            'image' => $request->image,
            'description' => $request->description,
            'content' => $request->content,
            'status' => $request->status,
            'feature' => $request->feature,
            'type' => 'category',
            'cat_id' => $request->cat_id,
            'seo_title' => $request->seo_title,
            'seo_description' => $request->seo_description,
            'user_id'=> Auth::id()
        ));
        return response()->json([
            'status' => 'success',
            'alert' => 'Sửa thành công!',
            'url' => route('category')
        ]);
    }
    public function delete(Request $request)
    {
        $check = Category::select('id')->where([['type','category'],['cat_id',$request->id]])->get();
        if($check->count() > 0){
            return response()->json([
                'status' => 'error',
                'errors' => array('alert' => array('0' => 'Chuyên mục chứa danh mục con, không thể xóa!'))
            ]);
        }else{
            $data = Category::findOrFail($request->id)->delete();
            if($request->page !=""){
                $url = route('category').'?page='.$request->page;
            }else{
                $url = route('category');
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => route('category')
            ]);
        }
    }
    public function status(Request $request){
        Category::where('id',$request->id)->update(array(
            'status' => $request->status
        ));
        return response()->json([
            'status' => 'success',
            'alert' => 'Đổi trạng thái thành công!',
            'url' => route('category')
        ]);
    }
    public function sort(){
        active('post','category');
        return view('Category::sort');
    }
    public function tree(Request $req) {
        $sort = $req->sortable;
        if(isset($sort))
        {
            foreach($sort as $order => $value)
            {
                $id = (int)$value['item_id'];  
                Category::where('id',$id)->update(array(
                    'cat_id' => (int)$value['parent_id'],
                    'sort' => $order
                ));
            }
        }
        $menu = Category::where([['type','category'],['status','1']])->orderBy('sort','asc')->get();
        $data['menu'] = $this->treeCate($menu,0);
        return view('Category::tree',$data);
    }
    public function treeCate($array = NULL,$parent = 0)
    {
        $html = "";
        if(isset($array) && !empty($array))
        {
            $html .= '<ol class="sortable">';
            foreach($array as  $item)
            {
                if($item->cat_id == $parent)
                {
                    $html .= '<li id="list_'.$item->id.'">';
                    $html .= '<div><i class="fa fa-angle-double-right"></i> '.$item->name.'</div>';
                    $html .= $this->treeCate($array, $item->id);
                    $html .= '</li>';
                }
            }
            $html .= '</ol>';
        }
        return $html;
    }
}
