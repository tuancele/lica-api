<?php

namespace App\Modules\Config\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Config\Models\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class ConfigController extends Controller
{
    public function index(Request $request){
        if($request->get('group') == "r2"){
            active('config','r2');
        } else {
            active('config','index');
        }
        
        if($request->get('group') != ""){
            if($request->get('group') == 'r2') {
                return view('R2::config');
            }
            return view('Config::'.$request->get('group'));
        }else{
            return view('Config::index');
        }
    }
    
    public function update(Request $request){
        updateConfig($request->data);
        return response()->json([
            'status' => 'success',
            'alert' => 'Cập nhật thành công!',
            'url' => ''
        ]);
    }
}
