<?php

namespace App\Modules\Order\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Location;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderDetail;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Pick\Models\Pick;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Validator;

class OrderController extends Controller
{
    use Location;

    public function index(Request $request)
    {
        active('order', 'list');
        $query = Order::query();

        if ($request->get('status') != "") {
            $query->where('status', $request->get('status'));
        }
        if ($request->get('ship') != "") {
            $query->where('ship', $request->get('ship'));
        }
        if ($request->get('code') != "") {
            $query->where('code', $request->get('code'));
        }
        if ($request->get('keyword') != "") {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->get('keyword') . '%')
                  ->orWhere('email', 'like', '%' . $request->get('keyword') . '%')
                  ->orWhere('phone', 'like', '%' . $request->get('keyword') . '%');
            });
        }

        $data['orders'] = $query->orderBy('id', 'desc')->paginate(20)->appends([
            'keyword' => $request->get('keyword'),
            'status' => $request->get('status'),
            'ship' => $request->get('ship')
        ]);

        return view('Order::index', $data);
    }

    public function view($code)
    {
        active('order', 'list');
        $order = Order::where('code', $code)->first();
        if (!$order) {
            return redirect('admin/order');
        }

        $data['list'] = OrderDetail::where('order_id', $order->id)->get();
        $delivery = Delivery::where('code', $code)->first();

        if ($delivery) {
            if (getConfig('ghtk_status')) {
                try {
                    $client = new Client();
                    $response = $client->request('GET', getConfig('ghtk_url') . "/services/shipment/v2/" . $delivery->label_id, [
                        'headers' => [
                            'Token' => getConfig('ghtk_token')
                        ]
                    ]);
                    $status = json_decode($response->getBody()->getContents());
                    $data['status'] = ($status->success) ? $status->order : '';
                } catch (\Exception $e) {
                    Log::error("GHTK Status Error: " . $e->getMessage());
                    $data['status'] = '';
                }
            }
            $data['delivery'] = $delivery;
        } else {
            $pick = Pick::where('status', '1')->orderBy('sort', 'asc')->first();
            $weight = OrderDetail::where('order_id', $order->id)->sum('weight');
            
            if ($pick) {
                $info = [
                    "pick_province" => $pick->province->name ?? '',
                    "pick_district" => $pick->district->name ?? '',
                    "pick_ward" => $pick->ward->name ?? '',
                    "pick_street" => $pick->street,
                    "pick_address" => $pick->address,
                    "province" => $order->province->name ?? '',
                    "district" => $order->district->name ?? '',
                    "ward" => $order->ward->name ?? '',
                    "address" => $order->address,
                    "weight" => $weight,
                    "value" => $order->total - $order->sale,
                    "transport" => 'road',
                    "deliver_option" => 'none',
                    "tags" => [0],
                ];
                
                $getFee = json_decode($this->getFee($info));
                $data['fee'] = ($getFee && $getFee->success) ? $getFee->fee : '';
            }
        }
        
        $data['order'] = $order;
        return view('Order::view', $data);
    }

    public function getFee($data)
    {
        try {
            $client = new Client();
            $response = $client->request('GET', getConfig('ghtk_url') . "/services/shipment/fee", [
                'headers' => [
                    'Token' => getConfig('ghtk_token')
                ],
                'query' => $data
            ]);
            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            Log::error("GHTK Fee Error: " . $e->getMessage());
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function postUpdate(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'code' => 'required',
            'status' => 'required|integer',
        ]);

        if ($validator->fails()) {
             return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        $order = Order::where('code', $req->code)->first();
        if (!$order) {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Đơn hàng không tồn tại!']]
            ]);
        } else {
            Order::where('id', $order->id)->update([
                'content' => $req->content,
                'status' => $req->status,
                'payment' => $req->payment,
                'ship' => $req->ship,
                'user_id' => Auth::id()
            ]);
            return response()->json([
                'status' => 'success',
                'alert' => 'Cập nhật thành công!',
                'url' => '/admin/order/view/' . $req->code
            ]);
        }
    }

    public function delete(Request $request)
    {
        $detail = Order::where([['code', $request->id], ['status', '0']])->first();
        if ($detail) {
            Order::where('code', $request->id)->delete();
            OrderDetail::where('order_id', $detail->id)->delete();
            
            $url = route('order');
            if ($request->page != "") {
                $url .= '?page=' . $request->page;
            }
            
            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => $url
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'alert' => 'Xóa không thành công!',
            ]);
        }
    }
}
