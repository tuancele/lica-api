<?php

namespace App\Modules\Delivery\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderDetail;
use App\Modules\Product\Models\Variant;
use App\Modules\Pick\Models\Pick;
use Validator;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Traits\Sendmail;

class GHTKController extends Controller
{
    use Sendmail;
    private $model;
    private $controller = 'delivery';
    private $view = 'Delivery';
    public function __construct(Delivery $model){
        $this->model = $model;
    }
    public function index(Request $request)
    {
        active('delivery','ghtk');
        if(getConfig('ghtk_status') == 0){
            return response()->json(['status' => 'false','message' => 'GHTK chưa được kích hoạt'],403);
        }
        $data['list'] = $this->model::where('type','ghtk')->where(function ($query) use ($request) {
            if($request->get('status') != "") {
	            $query->where('status', $request->get('status'));
            }
            if($request->get('keyword') != "") {
	            $query->where('name','like','%'.$request->get('keyword').'%');
	        }
        })->paginate(10)->appends($request->query());
        return view($this->view.'::ghtk.index',$data);
    }

    public function create(Request $request){
        active('delivery','ghtk');
        if(getConfig('ghtk_status') == 0){
            return response()->json(['status' => 'false','message' => 'GHTK chưa được kích hoạt'],403);
        }
        $detail =  Order::where([['status','!=','2'],['code',$request->id]])->first();
        if(!isset($detail) && empty($detail)){
            return response()->json(['status' => 'error','message' => 'Đơn hàng không tồn tại']);
        }
        $data['order'] = $detail;
        $weight = OrderDetail::select('weight')->where('order_id',$detail->id)->get()->sum('weight');
        $data['weight'] = $weight;
        $picks = Pick::where('status','1')->orderBy('sort','asc')->get();
        if($picks->count() > 0){
            $data['picks'] = $picks;
            $info = array(
                "pick_province" => $picks[0]->province->name??'',
                "pick_district" => $picks[0]->district->name??'',
                "pick_ward" => $picks[0]->ward->name??'',
                "pick_street" => $picks[0]->street,
                "pick_address"=> $picks[0]->address,
                "province" => $detail->province->name??'',
                "district" => $detail->district->name??'',
                "ward" => $detail->ward->name??'',
                "address" => $detail->address,
                "weight" => $weight,
                "value" => $detail->total - $detail->sale,
                "transport" => 'road',
                "deliver_option" => 'none',
                "tags"  => [0],
            );
            $getFee = json_decode($this->getFee($info));
            $data['success'] = $getFee->success;
            $data['message'] = $getFee->message;
            $data['fee'] = ($getFee->success)?$getFee->fee:'';
            $view = view($this->view.'::.ghtk.modal',$data)->render();
            return response()->json(['status' => 'success','message' => '','view' => $view]);
        }else{
            return response()->json(['status' => 'error','message' => 'Chưa có địa chỉ lấy hàng']);
        }
        
    }

    public function store(Request $request){
        if(getConfig('ghtk_status') == 0){
            return response()->json(['status' => 'false','message' => 'GHTK chưa được kích hoạt'],403);
        }
        $detail =  Order::where([['status','!=','2'],['code',$request->code]])->first();
        if(!isset($detail) && empty($detail)){
            return response()->json(['status' => 'error','message' => 'Đơn hàng không tồn tại']);
        }
        $list = OrderDetail::where('order_id',$detail->id)->get();
        $products = array();
        if($list->count() > 0){
            foreach ($list as $item) {
                $weight = ($item->weight != 0 && $item->qty != 0)?$item->weight/$item->qty:0;
                array_push($products, array(
                    'name' => $item->name,
                    'price' => $item->price,
                    'weight' => $weight,
                    'quantity' =>  $item->qty,
                    'product_code' => "",
                ));
            }
        }
        $pick = Pick::where([['status','1'],['id',$request->pick_id]])->orderBy('sort','asc')->first();
        if(!isset($pick) && empty($pick)){
            return response()->json(['status' => 'error','message' => 'Chưa có địa chỉ lấy hàng']);
        }
        $mang = array(
            'products' => $products,
            'order' => array(
                "id" => $detail->code,
                "pick_name" => $pick->name,
                "pick_address" => $pick->address,
                "pick_province" => $pick->province->name??'',
                "pick_district" => $pick->district->name??'',
                "pick_ward" => $pick->ward->name??'',
                "pick_tel" => $pick->tel,
                "pick_street" => $pick->street,
                "pick_email" => $pick->email,
                "pick_money" => $request->thuho,
                "tel" => $detail->phone,
                "name" => $detail->name,
                "address" => $detail->address,
                "province" => $detail->province->name,
                "district" => $detail->district->name,
                "ward" => $detail->ward->name,
                "hamlet" => $request->hamlet,
                "is_freeship" => $request->is_freeship,
                "note" => $request->note,
                "value" => $request->giatridon,
                "transport" => $request->transport,
                "pick_date" =>$request->pick_date,
                "pick_option" => $request->pick_option,      
                "deliver_option" => ($request->transport == "xteam")?"xteam":"none",
                "pick_option" => $request->pick_option,
                "pick_work_shift" => $request->pick_work_shift,
                "tags"  => [$request->tags],
            )
        );
        $order = json_encode($mang);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => getConfig('ghtk_url')."/services/shipment/order",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $order,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Token: ".getConfig('ghtk_token'),
                "Content-Length: " . strlen($order),
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        header('Content-Type: text/html; charset=utf-8');
        $result = json_decode($response);
        if($result->success){
            Order::where('id',$detail->id)->update(array(
                'ship' => '1',
                'fee_ship' => $result->order->fee
            ));
            Delivery::insertGetId(
                [
                    'label_id' => $result->order->label,
                    'partner_id' => $result->order->partner_id,
                    'status' => $result->order->status_id,
                    'code' => $detail->code,
                    'area' => $result->order->area,
                    'fee' => $result->order->fee,
                    'insurance_fee' => $result->order->insurance_fee,
                    'estimated_pick_time' => $result->order->estimated_pick_time,
                    'estimated_deliver_time' => $result->order->estimated_deliver_time,
                    'pick_id' => $request->pick_id,
                    'user_id'=> Auth::id(),
                    'type' => 'ghtk',
                    'created_at' => date('Y-m-d H:i:s')
                ]
            );
            if(getConfig('delivery_mail_status')){
                $subject = getConfig('delivery_mail_title');
                $a_search =  array('{site_title}','{ship_id}','{order_id}','{estimated_deliver}');
                $replace = array(getConfig('site_name'),$result->order->label,$detail->code,$result->order->estimated_deliver_time);
                $subject = str_replace($a_search,$replace,$subject);
                $body = array(
                    'title' => $subject,
                    'content' => str_replace($a_search, $replace, getConfig('delivery_mail_content'))
                );
                //$this->send($this->view.'::.ghtk.mail',$subject,getConfig('reply_email'),$body);
            }
            
        }else{
            // return response()->json([
            //     'status' => 'error',
            //     'errors' => array('alert' => array('0' => $result->message))
            // ]);
        }
        echo $response;
    }

    public function testSendmail(){
        $subject = getConfig('delivery_mail_title');
        $a_search =  array('{site_title}','{ship_id}','{order_id}','{estimated_deliver}');
        $replace = array(getConfig('site_name'),"ABc123","1232132","20/05/2023");
        $subject = str_replace($a_search,$replace,$subject);
        $body = array(
            'title' => $subject,
            'content' => str_replace($a_search, $replace, getConfig('delivery_mail_content'))
        );
        $this->send($this->view.'::.ghtk.mail',$subject,getConfig('reply_email'),$body);
    }

    public function getFee($data){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => getConfig('ghtk_url')."/services/shipment/fee?" . http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => array(
                "Token:".getConfig('ghtk_token')
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function printLabel($label){
        if(getConfig('ghtk_status') == 0){
            return response()->json(['status' => 'false','message' => 'GHTK chưa được kích hoạt'],403);
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => getConfig('ghtk_url')."/services/label/" . $label,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => array(
                "Token:".getConfig('ghtk_token')
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $data['response'] = $response;
        return response()->view($this->view.'::ghtk.print', $data, 200)->header('Content-Type', 'application/pdf');
    }

    public function cancel(Request $request){
        if(getConfig('ghtk_status') == 0){
            return response()->json(['status' => 'false','message' => 'GHTK chưa được kích hoạt'],403);
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => getConfig('ghtk_url')."/services/shipment/cancel/" . $request->id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => array(
                "Token:".getConfig('ghtk_token')
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($response);
        if($result->success == true){
            $delivery = Delivery::where('label_id',$request->id)->first();
            Delivery::where('label_id',$request->id)->delete();
            Order::where('code',$delivery->code)->update(['ship' => '4']);
        }
        return response()->json([
            'status' => 'success',
            'alert' => $result->message,
            'url' => '/admin/delivery/ghtk'
        ]);
    }
}
