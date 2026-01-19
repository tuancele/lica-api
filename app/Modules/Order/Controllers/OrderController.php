<?php

namespace App\Modules\Order\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Location;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderDetail;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Pick\Models\Pick;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Models\ProductWarehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
            $oldStatus = $order->status;
            $oldShip = $order->ship;
            
            Order::where('id', $order->id)->update([
                'content' => $req->content,
                'status' => $req->status,
                'payment' => $req->payment,
                'ship' => $req->ship,
                'user_id' => Auth::id()
            ]);
            
            // Auto create import receipt if order is cancelled or returned
            // Only create if status/ship changed to cancelled/returned
            if (($req->status == 2 && $oldStatus != 2) || ($req->ship == 3 && $oldShip != 3)) {
                try {
                    // Reload order to get updated values
                    $order->refresh();
                    $this->createImportReceiptFromOrder($order);
                } catch (\Exception $e) {
                    Log::error("Auto create import receipt error for order " . $order->code . ": " . $e->getMessage());
                    // Don't fail the order update if import receipt creation fails
                }
            }
            
            return response()->json([
                'status' => 'success',
                'alert' => 'Cập nhật thành công!',
                'url' => '/admin/order/view/' . $req->code
            ]);
        }
    }

    /**
     * Auto create import receipt when order is cancelled or returned
     * @param Order $order
     * @return bool
     */
    private function createImportReceiptFromOrder(Order $order): bool
    {
        // Check if import receipt already exists for this order
        // Check by order ID in content to avoid duplicates
        $existingReceipt = Warehouse::where('content', 'like', '%ID ' . $order->id)
            ->where('type', 'import')
            ->first();
        
        if ($existingReceipt) {
            Log::info("Import receipt already exists for order ID: " . $order->id);
            return false;
        }
        
        // Content format: "Đơn hàng thất bại/Hoàn trả ID {order_id}"
        // No VAT invoice (leave blank as requested)
        $content = 'Đơn hàng thất bại/Hoàn trả ID ' . $order->id;
        
        // Generate unique code for import receipt
        $importCode = 'NH-' . $order->code . '-' . time();
        
        // Check if code already exists, if yes, append random number
        while (Warehouse::where('code', $importCode)->exists()) {
            $importCode = 'NH-' . $order->code . '-' . time() . '-' . rand(1000, 9999);
        }
        
        // Determine subject based on status
        // Priority: ship=3 (returned) > status=2 (cancelled)
        $statusText = ($order->ship == 3) ? 'Hoàn trả' : (($order->status == 2) ? 'Đơn hàng thất bại' : 'Hoàn trả');
        
        // Create warehouse entry
        $warehouseId = Warehouse::insertGetId([
            'code' => $importCode,
            'subject' => $statusText . ' - ' . $order->code,
            'content' => $content,
            'type' => 'import',
            'created_at' => date('Y-m-d H:i:s'),
            'user_id' => Auth::id(),
        ]);
        
        if ($warehouseId > 0) {
            // Get order details
            $orderDetails = OrderDetail::where('order_id', $order->id)->get();
            
            if ($orderDetails->count() > 0) {
                foreach ($orderDetails as $detail) {
                    // Only create ProductWarehouse if variant_id exists
                    if ($detail->variant_id) {
                        ProductWarehouse::insert([
                            'variant_id' => $detail->variant_id,
                            'price' => $detail->price ?? 0,
                            'qty' => $detail->qty ?? 0,
                            'type' => 'import',
                            'warehouse_id' => $warehouseId,
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }
            
            Log::info("Auto created import receipt: " . $importCode . " for order: " . $order->code . " (ID: " . $order->id . ")");
            return true;
        }
        
        return false;
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
