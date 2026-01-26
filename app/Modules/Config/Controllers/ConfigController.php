<?php

declare(strict_types=1);

namespace App\Modules\Config\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    public function index(Request $request)
    {
        if ($request->get('group') == 'r2') {
            active('config', 'r2');
        } else {
            active('config', 'index');
        }

        if ($request->get('group') != '') {
            if ($request->get('group') == 'r2') {
                return view('R2::config');
            }

            return view('Config::'.$request->get('group'));
        } else {
            return view('Config::index');
        }
    }

    public function update(Request $request)
    {
        updateConfig($request->data);

        return response()->json([
            'status' => 'success',
            'alert' => 'Cập nhật thành công!',
            'url' => '',
        ]);
    }
}
