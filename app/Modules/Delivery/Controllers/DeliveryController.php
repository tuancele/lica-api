<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function setting(Request $request)
    {
        active('delivery', 'setting');

        return view('Delivery::setting');
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
