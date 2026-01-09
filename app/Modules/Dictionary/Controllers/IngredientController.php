<?php
namespace App\Modules\Dictionary\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Dictionary\Models\IngredientPaulas;
use App\Modules\Dictionary\Models\IngredientCategory;
use App\Modules\Dictionary\Models\IngredientBenefit;
use App\Modules\Dictionary\Models\IngredientRate;
use App\Modules\Product\Models\Product;
use Illuminate\Support\Facades\Auth;
use Drnxloc\LaravelHtmlDom\HtmlDomParser;
use Validator;
use Exception;

class IngredientController extends Controller
{
    private $model;
    private $controller = 'ingredient';
    private $view = 'Dictionary';
    public function __construct(IngredientPaulas $model){
        $this->model = $model;
    }
    public function index(Request $request)
    {
        active('dictionary','ingredient');
        $data['posts'] = $this->model::where(function ($query) use ($request) {
            if($request->get('status') != "") {
	            $query->where('status', $request->get('status'));
            }
            if($request->get('keyword') != "") {
	            $query->where('name','like','%'.$request->get('keyword').'%');
	        }
        })->orderBy('created_at','desc')->paginate(20)->appends(['keyword' => $request->get('keyword'),'status' => $request->get('status')]);
        return view($this->view.'::ingredient.index',$data);
    }
    public function create(){
        //$this->authorize('post-create');
        active('dictionary','ingredient');
        $data['categories'] = IngredientCategory::where('status','1')->orderBy('sort','asc')->get();
        $data['rates'] = IngredientRate::where('status','1')->orderBy('sort','asc')->get();
        $data['benefits'] = IngredientBenefit::where('status','1')->orderBy('sort','asc')->get();
        return view($this->view.'::ingredient.create',$data);
    }
    public function edit($id){
        active('dictionary','ingredient');
        $post = $this->model::find($id);
        //$this->authorize($post,'post-edit');
        if(!isset($post) && empty($post)){
            return redirect()->route('post');
        }
        $data['detail'] = $post;
        $data['categories'] = IngredientCategory::where('status','1')->orderBy('sort','asc')->get();
        $data['rates'] = IngredientRate::where('status','1')->orderBy('sort','asc')->get();
        $data['benefits'] = IngredientBenefit::where('status','1')->orderBy('sort','asc')->get();
        $data['dcat'] = json_decode($post->cat_id);
        $data['dben'] = json_decode($post->benefit_id);
        $data['products'] = Product::select('id','name','image')->where([['status','1'],['type','product'],['ingredient','like','%'.$post->name.'%']])->get(); 
        return view($this->view.'::ingredient.edit',$data);
    }
    public function update(Request $request)
    {
        //$post = $this->model::find($request->id);
        //$this->authorize($post,'post-edit');
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
        $this->model::where('id',$request->id)->update(array(
            'name' => $request->name,
            'rate_id' => $request->rate_id,
            'description' => $request->description,
            'content' => $request->content,
            'disclaimer' => $request->disclaimer,
            'reference' => $request->reference,
            'shortcode' => $request->shortcode,
            'glance' => $request->glance,
            'status' => $request->status,
            'cat_id' => json_encode($request->cat_id),
            'benefit_id' => json_encode($request->benefit_id),
            'seo_title' => $request->seo_title,
            'seo_description' => $request->seo_description,
            'user_id'=> Auth::id()
        ));
        return response()->json([
            'status' => 'success',
            'alert' => 'Sửa thành công!',
            'url' => route('dictionary.ingredient')
        ]);
    }

    public function store(Request $request)
    {   
        //$this->authorize('post-create');
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:250',
            'slug' => 'required|min:1|max:250|unique:ingredient_paulas,slug',
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
                'rate_id' => $request->rate_id,
                'description' => $request->description,
                'content' => $request->content,
                'disclaimer' => $request->disclaimer,
                'reference' => $request->reference,
                'glance' => $request->glance,
                'status' => $request->status,
                'cat_id' => json_encode($request->cat_id),
                'benefit_id' => json_encode($request->benefit_id),
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
                'url' => route('dictionary.ingredient')
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
            $url = route('dictionary.ingredient').'?page='.$request->page;
        }else{
            $url = route('dictionary.ingredient');
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
            'url' => route('dictionary.ingredient')
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
                'url' => route('dictionary.ingredient')
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
                'url' => route('dictionary.ingredient')
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
                'url' => route('dictionary.ingredient')
            ]);
        }
    }

    public function crawl(){
        active('dictionary','ingredient');
        $link = "https://www.paulaschoice.com/ingredient-dictionary?csortb1=ingredientNotRated&csortd1=1&csortb2=ingredientRating&csortd2=2&csortb3=name&csortd3=1&start=0&sz=1&ajax=true";
        $ch = curl_init($link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($ch);
        curl_close($ch);
        $api = json_decode($content, true);
        $total = $api['paging']['total'];
        $page =  ceil($total/2000);
        $data['page'] = $page;
        $data['total'] = $total;
        return view($this->view.'::ingredient.crawl',$data); 
    }

    public function getData(Request $request){
        try{
            $i = $request->offset;
            $rerult = '';
            $link = "https://www.paulaschoice.com/ingredient-dictionary?start=".$i."&sz=2000&ajax=true";
            $ch = curl_init($link);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $content = curl_exec($ch);
            curl_close($ch);
            $api = json_decode($content, true);
            $pages = $api['paging'];
            $ingredients = $api['ingredients'];
            $status = "";
            $j = 0;
            if(isset($ingredients) && !empty($ingredients)){
                foreach ($ingredients as $key => $value) {
                    $check = $this->model::where('slug',$value['id'])->first();
                    if(isset($check) && !empty($check)){
                        $url = 'https://www.paulaschoice.com'.$value['url'].'&ajax=true';
                        $this->detail($url,$check->id);
                        $status = "<span>Cập nhật thành công</span>";
                    }else{
                        $id = $this->model::insertGetId(
                            [
                                'name' => $value['name'],
                                'slug' => $value['id'],
                                'description' => $value['description'],
                                'status' => '1',
                                'seo_description' => $value['description'],
                                'seo_title' => $value['name'],
                                'user_id'=> Auth::id(),
                                'created_at' => date('Y-m-d H:i:s')
                            ]
                        );
                        if($id > 0){
                            $url = 'https://www.paulaschoice.com'.$value['url'].'&ajax=true';
                            $this->detail($url,$id);
                        }
                        $status = "<span>Thêm thành công</span>";
                    } 
                    $j++;
                    $rerult .= '<p>'.$j.' - Thành phần: '.$value['name']. ' - '.$status.'</p>';
                }  
            }
            return response()->json([
                'status' => 'success',
                'message' => $rerult
            ]);
        }catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getDom($link){
        $ch = curl_init($link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($ch);
        curl_close($ch);
        $dom = HtmlDomParser::str_get_html($content);
        return $dom;
    }

    public function detail($link,$id){
        try{
            $ch = curl_init($link);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $content = curl_exec($ch);
            curl_close($ch);
            $rooms = json_decode($content, true);
            $content2 = '';
            if(isset($rooms['description']) && !empty($rooms['description'])){
                $description = $rooms['description'];
                foreach ($description as $key => $value) {
                    $stt = count($value['text'])-1;
                    $content2 .='<p>'.$value['text'][$stt].'</p>';
                }
            }
            $reference = '';
            if(isset($rooms['references']) && !empty($rooms['references'])){
                $references = $rooms['references'];
                foreach ($references as $key1 => $val1) {
                    $reference .='<p>'.$val1.'</p>';
                }
            }
            $disclaimer = '';
            if(isset($rooms['strings']) && !empty($rooms['strings'])){
                $strings = $rooms['strings'];
                if(isset($strings['disclaimer'])){
                    $disclaimer = $strings['disclaimer'];
                }
            }
            
            $glance = '';
            if(isset($rooms['keyPoints']) && !empty($rooms['keyPoints'])){
                $keyPoints = $rooms['keyPoints'];
                $glance .= '<ul>';
                foreach ($keyPoints as $key2 => $val2) {
                    $glance .='<li>'.$val2.'</li>';
                }
                $glance .= '</ul>';
            }
            $this->model::where('id',$id)->update(
                [
                    'name' => $rooms['name'],
                    'rate_id' => $this->getRate($rooms['rating']),
                    'content' => $content2,
                    'reference' => $reference,
                    'disclaimer' => $disclaimer,
                    'glance' => $glance,
                    'status' => '1',
                    'cat_id' => json_encode($this->getCategory($rooms['relatedCategories'])),
                    'benefit_id' => json_encode($this->getBenefit($rooms['benefits'])),
                ]
            );
        }catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getBenefit($benefits){
        $array = array();
        if(isset($benefits) && !empty($benefits)){
            foreach ($benefits as $key => $value) {
                $detail = IngredientBenefit::where('name',$value['name'])->first();
                if(isset($detail) && !empty($detail)){
                    array_push($array, strval($detail->id));
                }
            }
        }
        return $array;
    }

    public function getCategory($categories){
        $array = array();
        if(isset($categories) && !empty($categories)){
            foreach ($categories as $key => $value) {
                $detail = IngredientCategory::where('name',$value['name'])->first();
                if(isset($detail) && !empty($detail)){
                    array_push($array, strval($detail->id));
                }
            }
        }
        return $array;
    }

    public function getRate($rate){
        if($rate != ""){
            $detail = IngredientRate::where('name',$rate)->first();
            if(isset($detail) && !empty($detail)){
                return $detail->id;
            }else{
                return '0';
            }
        }else{
            return '0';
        }
    }

    public function updateIngredient(){
        try{
            $link = 'https://www.paulaschoice.com/ingredient-dictionary/ingredient-aha.html?sz=2000&fdid=ingredients&start=0&ajax=true';
            $id = '23';
            $ch = curl_init($link);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $content = curl_exec($ch);
            curl_close($ch);
            $rooms = json_decode($content, true);
            $description = $rooms['description'];
            $content2 = '';
            if(isset($description) && !empty($description)){
                foreach ($description as $key => $value) {
                    $stt = count($value['text'])-1;
                    $content2 .='<p>'.$value['text'][$stt].'</p>';
                }
            }
           
            $reference = '';
            $references = $rooms['references'];
            if(isset($references) && !empty($references)){
                foreach ($references as $key1 => $val1) {
                    $reference .='<p>'.$val1.'</p>';
                }
            }
            $disclaimer = '';
            $strings = $rooms['strings'];
            if(isset($strings) && !empty($strings)){
                if(isset($strings['disclaimer'])){
                    $disclaimer = $strings['disclaimer'];
                }
            }
            $glance = '';
            if(isset($rooms['keyPoints']) && !empty($rooms['keyPoints'])){
                $keyPoints = $rooms['keyPoints'];
                $glance .= '<ul>';
                foreach ($keyPoints as $key2 => $val2) {
                    $glance .='<li>'.$val2.'</li>';
                }
                $glance .= '</ul>';
            }
            $this->model::where('id',$id)->update(
                [
                    'name' => $rooms['name'],
                    'rate_id' => $this->getRate($rooms['rating']),
                    'content' => $content2,
                    'reference' => $reference,
                    'disclaimer' => $disclaimer,
                    'glance' => $glance,
                    'status' => '1',
                    'cat_id' => json_encode($this->getCategory($rooms['relatedCategories'])),
                    'benefit_id' => json_encode($this->getBenefit($rooms['benefits'])),
                ]
            );
        }catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
