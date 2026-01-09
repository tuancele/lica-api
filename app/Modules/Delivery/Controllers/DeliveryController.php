<?php

namespace App\Modules\Delivery\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Config\Models\Config;
class DeliveryController extends Controller
{
    public function setting(Request $request){
        active('delivery','setting');
        return view('Delivery::setting');
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
