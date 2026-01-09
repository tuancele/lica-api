<?php
namespace App\Modules\Compare\Controllers;
use Illuminate\Http\Request;
use App\Modules\Compare\Models\Compare;
use App\Modules\Compare\Models\Store;
use App\Modules\Compare\Models\Draff;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Drnxloc\LaravelHtmlDom\HtmlDomParser;
use Validator;
use Illuminate\Support\Facades\Auth;
use Session;
use Exception;

class StoreController extends Controller
{
    private $model;
    private $controller = 'compare';
    private $view = 'Compare';
    public function __construct(Store $model){
        $this->model = $model;
    }

    public function index(Request $request){
		active('compare','store');
        $data['list'] = $this->model::where(function ($query) use ($request) {
            if($request->get('status') != "") {
	            $query->where('status', $request->get('status'));
            }
            if($request->get('keyword') != "") {
	            $query->orWhere('name','like','%'.$request->get('keyword').'%');
	        }
        })->orderBy('id','asc')->paginate(20)->appends(['keyword' => $request->get('keyword'),'status' => $request->get('status')]);
		return view($this->view.'::store.index',$data);
    }

    public function edit($id){
        active('compare','store');
        $post = $this->model::find($id);
        if(!isset($post) && empty($post)){
            return redirect()->route('compare.store');
        }
        $data['detail'] = $post;
        return view($this->view.'::store.edit',$data);
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
        $this->model::where('id',$request->id)->update(array(
			'name' => $request->name,
			'logo' => $request->image,
			'status' => $request->status,
            'user_id'=> Auth::id()
        ));
        return response()->json([
            'status' => 'success',
            'alert' => 'Sửa thành công!',
            'url' => route('compare.store')
        ]);
    }


}