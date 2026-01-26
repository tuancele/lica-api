<?php

declare(strict_types=1);

namespace App\Modules\Setting\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        active('setting', 'index');
        if ($request->get('group') != '') {
            return view('Setting::'.$request->get('group'));
        } else {
            return view('Setting::index');
        }
    }

    public function update(Request $request)
    {
        updateSetting($request->data);

        return response()->json([
            'status' => 'success',
            'alert' => 'Cập nhật thành công!',
            'url' => '',
        ]);
    }
}
