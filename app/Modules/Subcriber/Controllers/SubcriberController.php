<?php

declare(strict_types=1);
namespace App\Modules\Subcriber\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Subcriber\Models\Subcriber;
use Validator;
use Illuminate\Support\Facades\Auth;
use Session;
class SubcriberController extends Controller
{
    private $model;
    private $controller = 'subcriber';
    private $view = 'Subcriber';
    public function __construct(Subcriber $model){
        $this->model = $model;
    }
    public function index(Request $request)
    {
        active('subcriber','list');
        $data['list'] = $this->model::where(function ($query) use ($request) {
            if($request->get('keyword') != "") {
	            $query->where('email','like','%'.$request->get('keyword').'%');
	        }
        })->latest()->paginate(20)->appends($request->query());
        return view($this->view.'::index',$data);
    }
   
    public function delete(Request $request)
    {
        $data = $this->model::findOrFail($request->id)->delete();
        if($request->page !=""){
            $url = route('subcriber').'?page='.$request->page;
        }else{
            $url = route('subcriber');
        }
        return response()->json([
            'status' => 'success',
            'alert' => 'Xóa thành công!',
            'url' => $url
        ]);
    }
}
