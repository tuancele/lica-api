<?php

namespace App\Modules\Ingredient\Controllers;

use Illuminate\Http\Request;
use App\Modules\Ingredient\Models\Ingredient;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Drnxloc\LaravelHtmlDom\HtmlDomParser;
use Validator;
use Illuminate\Support\Facades\Auth;
use Session;

class IngredientController extends Controller
{
    private $model;
    private $controller = 'ingredient';
    private $view = 'Ingredient';
    public function __construct(Ingredient $model){
        $this->model = $model;
    }

    public function getList(Request $request){
        $url = 'https://api.ewg.org/autocomplete?uuid=auto&search='.$request->s;
        $client = new \GuzzleHttp\Client();
        $request = $client->get($url);
        $response = $request->getBody()->getContents();
        $array = json_decode($response)->ingredients;
        if(isset($array) && !empty($array)){
            foreach($array as $value){
                echo '<a href="javascript:;" data-href="https://www.ewg.org'.$value->url.'/">'.$value->name.'</a>';
            }
        }
    }

    public function getDetail(Request $request){
        
        $link = $request->href;
        $ch = curl_init($link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($ch);
        curl_close($ch);
        $dom = HtmlDomParser::str_get_html($content);
        foreach ($dom->find('.product-concerns-and-info') as $content) {
            echo $content;
        }
    }

    public function index(Request $request){
        active('product','ingredient');
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
        active('product','ingredient');
        return view($this->view.'::create');
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
                'content' => $request->content,
                'slug' => \Str::slug($request->name),
                'status' => $request->status,
                'link' => $request->link,
                'user_id'=> Auth::id(),
                'created_at' => date('Y-m-d H:i:s')
            ]
        );
        if($id > 0){
            return response()->json([
                'status' => 'success',
                'alert' => 'Thêm thành công!',
                'url' => route('ingredient')
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'errors' => array('alert' => array('0' => 'Thêm không thành công!'))
            ]);
        }
    }

    public function edit($id){
        active('product','ingredient');
        $detail = $this->model::find($id);
        if(!isset($detail) && empty($detail)){
            return redirect()->route('ingredient');
        }
        $data['detail'] = $detail;
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
            'slug' => $request->slug,
			'status' => $request->status,
            'content' => $request->content,
            'link' => $request->link,
            'user_id'=> Auth::id()
        ));
        if($up > 0){
            return response()->json([
                'status' => 'success',
                'alert' => 'Sửa thành công!',
                'url' => route('ingredient')
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'errors' => array('alert' => array('0' => 'Sửa không thành công!'))
            ]);
        }
    }

    public function delete(Request $request)
    {
        $data = $this->model::findOrFail($request->id)->delete();
        if($request->page !=""){
            $url = route('ingredient').'?page='.$request->page;
        }else{
            $url = route('ingredient');
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
            'url' => route('ingredient')
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
                $this->model::where('id',$value)->update(array(
                    'status' => '0'
                ));
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Ẩn thành công!',
                'url' => route('ingredient')
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
                'url' => route('ingredient')
            ]);
        }else{
            foreach($check as $key => $value){
                $this->model::where('id',$value)->delete();
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => route('ingredient')
            ]);
        }
    }
}
