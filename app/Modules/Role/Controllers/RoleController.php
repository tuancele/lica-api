<?php

namespace App\Modules\Role\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Permission\Models\Permission;
use App\Modules\Role\Models\Role;
use App\Modules\Role\Models\RolePermission;
use Illuminate\Support\Facades\Auth;
use Validator;

class RoleController extends Controller
{
    private $model;
    private $view = 'Role';
    public function __construct(Role $model){
        $this->model = $model;
    }
    public function index(Request $request){
        // $this->authorize('list-role');
        $data['list'] = $this->model::where(function ($query) use ($request) {
            if($request->get('status') != "") {
                $query->where('status', $request->get('status'));
            }
            if($request->get('keyword') != "") {
                $query->where('name','like','%'.$request->get('keyword').'%');
            }
        })->orderBy('id','asc')->paginate(20);
    	return view($this->view.'::index',$data);
    }
    public function create(){
        // $this->authorize('create-role');
    	$data['permissions'] = Permission::where('parent_id','0')->orderBy('sort','asc')->get();
    	return view($this->view.'::create',$data);
    }
    public function postCreate(Request $request){
    	$this->authorize('create-role');
    	$validator = Validator::make($request->all(), [
            'name' => 'required',
        ],[
            'name.required' => 'Tên quyền không được bỏ trống',
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
                'status' => $request->status,
                'user_id'=> Auth::id(),
                'created_at' => date('Y-m-d H:i:s')
            ]
        );
        if($id > 0){
        	$pers = $request->per;
        	if(isset($pers) && !empty($pers)){
        		foreach ($pers as $key => $value) {
        			RolePermission::insertGetId(
			            [
			                'role_id' => $id,
			                'permission_id' => $value,
			                'user_id'=> Auth::id(),
			                'created_at' => date('Y-m-d H:i:s')
			            ]
		        	);
        		}
        	}
            return response()->json([
                'status' => 'success',
                'message' => 'Thêm thành công!',
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Thêm không thành công!'
            ]);
        }
    }
    public function edit($id){
    	$this->authorize('edit-role');
    	$detail = $this->model::find($id);
    	if(!isset($detail) && empty($detail)){
    		return 'Không tồn tại sản phẩm này';
    	}
 		$data['permissions'] = Permission::where('parent_id','0')->orderBy('sort','asc')->get();
 		$rolepers = RolePermission::select('permission_id')->where('role_id',$id)->get();
 		$arr = array();
 		if($rolepers->count() > 0){
 			foreach ($rolepers as $value) {
 				array_push($arr, $value->permission_id);
 			}
 		}
 		$data['arr'] = $arr;
    	$data['detail'] = $detail;
    	return view($this->view.'::edit',$data);
    }
    public function update(Request $request){
    	$this->authorize('edit-role');
    	$validator = Validator::make($request->all(), [
            'name' => 'required',
        ],[
            'name.required' => 'Tên quyền không được bỏ trống',
        ]);
        if($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }
        $update = $this->model::where('id',$request->id)->update(array(
           	'name' => $request->name,
            'status' => $request->status,
            'user_id'=> Auth::id(),
        ));
        if($update > 0){
        	$delete = RolePermission::where('role_id',$request->id)->delete();
        	$pers = $request->per;
        	if(isset($pers) && !empty($pers)){
        		foreach ($pers as $key => $value) {
        			RolePermission::insertGetId(
			            [
			                'role_id' => $request->id,
			                'permission_id' => $value,
			                'user_id'=> Auth::id(),
			                'created_at' => date('Y-m-d H:i:s')
			            ]
		        	);
        		}
        	}
            // $this->addHistory(Auth::id(),'Sửa quyền <a href="/role/" target="_blank">'.$request->name.'</a>');
            return response()->json([
                'status' => 'success',
                'alert' => 'Sửa thành công!',
                'url' => '/admin/role'
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Sửa không thành công!'
            ]);
        }
    }
    public function delete(Request $request){
    	$this->authorize('delete-role');
    	$detail = $this->model::find($request->id);
    	if(isset($detail) && !empty($detail)){
    		$delete = $this->model::findOrFail($request->id)->delete();
	        if($delete > 0){
	        	RolePermission::where('role_id',$request->id)->delete();
	            // $this->addHistory(Auth::id(),'Xóa quyền <a href="/role" target="_blank">'.$detail->name.'</a>');
                return response()->json([
                    'status' => 'success',
                    'alert' => 'Xóa thành công!',
                    'url' => '/admin/role'
                ]);
	        }else{
	            return response()->json([
	                'status' => 'error',
	                'message' => 'Xóa không thành công!'
	            ]);
	        }
    	}else{
    		return response()->json([
                'status' => 'error',
                'message' => 'Dữ liệu không tồn tại!'
            ]);
    	}
    }
}