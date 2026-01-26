<?php

declare(strict_types=1);
namespace App\Modules\FooterBlock\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\FooterBlock\Models\FooterBlock;
use Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;

class FooterBlockController extends Controller
{
    private $model;
    private $view = 'FooterBlock';
    
    public function __construct(FooterBlock $model){
        $this->model = $model;
    }
    
    public function index(Request $request)
    {
        // #region agent log
        try {
            $logPath = base_path('.cursor/debug.log');
            $logDir = dirname($logPath);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            $logData = ['session_active' => Session::get('sidebar_active'), 'session_sub_active' => Session::get('sidebar_sub_active'), 'before_active' => true];
            @file_put_contents($logPath, json_encode(['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'B', 'location' => 'FooterBlockController.php:22', 'message' => 'Before active() call', 'data' => $logData, 'timestamp' => time() * 1000]) . "\n", FILE_APPEND);
        } catch (\Exception $e) {}
        // #endregion
        active('themes','footer-block');

        // Nếu bảng chưa tồn tại, trả về paginator rỗng để tránh lỗi
        if (!Schema::hasTable('footer_blocks')) {
            $data['blocks'] = new LengthAwarePaginator(
                [],
                0,
                10,
                1,
                [
                    'path'  => $request->url(),
                    'query' => $request->query(),
                ]
            );
            return view($this->view.'::index',$data);
        }

        // #region agent log
        try {
            $logData2 = ['session_active' => Session::get('sidebar_active'), 'session_sub_active' => Session::get('sidebar_sub_active'), 'after_active' => true];
            @file_put_contents($logPath, json_encode(['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'B', 'location' => 'FooterBlockController.php:27', 'message' => 'After active() call', 'data' => $logData2, 'timestamp' => time() * 1000]) . "\n", FILE_APPEND);
        } catch (\Exception $e) {}
        // #endregion

        $data['blocks'] = $this->model::where(function ($query) use ($request) {
            if($request->get('status') != "") {
                $query->where('status', $request->get('status'));
            }
            if($request->get('keyword') != "") {
                $query->where('title','like', '%'.$request->get('keyword').'%');
            }
        })->orderBy('sort','asc')->orderBy('id','desc')->paginate(10);
        return view($this->view.'::index',$data);
    }   
    
    public function create(){
        active('themes','footer-block');
        return view($this->view.'::create');
    }
    
    public function store(Request $request)
    {   
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|max:250',
        ],[
            'title.max' => 'Tiêu đề có độ dài tối đa 250 ký tự',
        ]);
        
        if($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }
        
        // Xử lý tags
        $tags = [];
        if($request->has('tag_names') && is_array($request->tag_names)) {
            foreach($request->tag_names as $key => $name) {
                if(!empty($name) && !empty($request->tag_urls[$key])) {
                    $tags[] = [
                        'name' => $name,
                        'url' => $request->tag_urls[$key]
                    ];
                }
            }
        }
        
        // Xử lý links
        $links = [];
        if($request->has('link_texts') && is_array($request->link_texts)) {
            foreach($request->link_texts as $key => $text) {
                if(!empty($text) && !empty($request->link_urls[$key])) {
                    $links[] = [
                        'text' => $text,
                        'url' => $request->link_urls[$key]
                    ];
                }
            }
        }
        
        $id = FooterBlock::insertGetId([
            'title' => $request->title,
            'tags' => json_encode($tags),
            'links' => json_encode($links),
            'status' => $request->status ?? 1,
            'sort' => $request->sort ?? 0,
            'user_id'=> Auth::id(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        if($id > 0){
            return response()->json([
                'status' => 'success',
                'alert' => 'Thêm thành công!',
                'url' => '/admin/footer-block'
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'errors' => array('alert' => array('0' => 'Thêm không thành công!'))
            ]);
        }
    }
    
    public function edit($id){
        active('themes','footer-block');
        $data['detail'] = FooterBlock::find($id);
        return view($this->view.'::edit',$data);
    }
    
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|max:250',
        ],[
            'title.max' => 'Tiêu đề có độ dài tối đa 250 ký tự',
        ]);
        
        if($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }
        
        // Xử lý tags
        $tags = [];
        if($request->has('tag_names') && is_array($request->tag_names)) {
            foreach($request->tag_names as $key => $name) {
                if(!empty($name) && !empty($request->tag_urls[$key])) {
                    $tags[] = [
                        'name' => $name,
                        'url' => $request->tag_urls[$key]
                    ];
                }
            }
        }
        
        // Xử lý links
        $links = [];
        if($request->has('link_texts') && is_array($request->link_texts)) {
            foreach($request->link_texts as $key => $text) {
                if(!empty($text) && !empty($request->link_urls[$key])) {
                    $links[] = [
                        'text' => $text,
                        'url' => $request->link_urls[$key]
                    ];
                }
            }
        }
        
        FooterBlock::where('id',$request->id)->update([
            'title' => $request->title,
            'tags' => json_encode($tags),
            'links' => json_encode($links),
            'status' => $request->status ?? 1,
            'sort' => $request->sort ?? 0,
            'user_id'=> Auth::id(),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        return response()->json([
            'status' => 'success',
            'alert' => 'Sửa thành công!',
            'url' => '/admin/footer-block'
        ]);
    }
    
    public function delete(Request $request)
    {
        FooterBlock::findOrFail($request->id)->delete();
        if($request->page !=""){
            $url = '/admin/footer-block?page='.$request->page;
        }else{
            $url = '/admin/footer-block';
        }
        return response()->json([
            'status' => 'success',
            'alert' => 'Xóa thành công!',
            'url' => $url
        ]);
    }
    
    public function status(Request $request){
        FooterBlock::where('id',$request->id)->update([
            'status' => $request->status
        ]);
        return response()->json([
            'status' => 'success',
            'alert' => 'Đổi trạng thái thành công!',
            'url' => '/admin/footer-block'
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
                FooterBlock::where('id',$value)->update(['status' => '0']);
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Ẩn thành công!',
                'url' => '/admin/footer-block'
            ]);
        }elseif($action == 1){
            foreach($check as $key => $value){
                FooterBlock::where('id',$value)->update(['status' => '1']);
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Hiển thị thành công!',
                'url' => '/admin/footer-block'
            ]);
        }else{
            foreach($check as $key => $value){
                FooterBlock::where('id',$value)->delete();
            }
            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => '/admin/footer-block'
            ]);
        }
    }
}
