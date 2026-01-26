<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contact\Models\Contact;
use App\Modules\Order\Models\Order;
use App\Modules\Post\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        active('dashboard', 'dashboard');
        $data['product'] = Post::where('type', 'product')->get()->count();
        $data['post'] = Post::where('type', 'post')->get()->count();
        $data['order'] = Order::select('id', 'total', 'fee_ship', 'sale')->where('status', '!=', '2')->get();
        $data['contact'] = Contact::select('id')->get()->count();
        $end = date('Y-m-d');
        $start = strtotime('-1 day', strtotime(date('Y-m-d')));
        $data['products'] = Order::join('orderdetail', 'orders.id', '=', 'orderdetail.order_id')->where('status', '!=', '2')->select('qty')->get();
        $data['block_chart'] = $this->block_chart($start, strtotime(date('Y-m-d')));

        return view('Dashboard::index', $data);
    }

    public function block_chart($start, $end)
    {
        $data['start'] = date('m/d/Y', $start);
        $data['end'] = date('m/d/Y', $end);
        $statis = [];
        for ($i = $start; $i <= strtotime(date('Y-m-d')); $i = $i + 86400) {
            $order = Order::select('id', 'total', 'fee_ship', 'sale')->where('status', '!=', '2')->whereDate('created_at', date('Y-m-d', $i))->get();
            array_push($statis, [
                'date' => date('Y-m-d', $i),
                'money' => array_sum(array_column($order->toArray(), 'total')),
            ]);
        }
        $data['statis'] = json_encode($statis);
        $data['sale_hot'] = DB::table('orderdetail')->select('product_id', DB::raw('SUM(qty) AS soluong'))->groupBy('product_id')->whereDate('created_at', '>=', date('Y-m-d', $start))->whereDate('created_at', '<=', date('Y-m-d', $end))->orderBy('soluong', 'desc')->limit('5')->get();

        return view('Dashboard::loadchart', $data);
    }

    public function loadchart(Request $req)
    {
        $time = $req->time;
        $mang = explode(' - ', $req->time);
        $end = strtotime($mang[1]);
        $start = strtotime($mang[0]);

        return $this->block_chart($start, $end);
    }

    public function order()
    {
        active('dashboard', 'dashboard');
        $now = date('Y-m-d');
        $yesterday = strtotime('-1 day', strtotime($now));
        $data['payment'] = Order::select('id')->where([['payment', '0'], ['status', '!=', '2']])->get()->count();
        $data['ship'] = Order::select('id')->where([['ship', '0'], ['status', '!=', '2']])->get()->count();
        $data['shipping'] = Order::select('id')->where([['ship', '1'], ['status', '!=', '2']])->get()->count();
        $data['cancel'] = Order::select('id')->where('status', '2')->get()->count();
        $data['payments'] = Order::select('id', 'code', 'created_at', 'total')->where([['payment', '0'], ['status', '!=', '2']])->whereDate('created_at', '>=', date('Y-m-d', $yesterday))->whereDate('created_at', '<=', date('Y-m-d', strtotime($now)))->limit(5)->get();
        $data['ships'] = Order::select('id', 'code', 'created_at', 'total')->where([['ship', '0'], ['status', '!=', '2']])->whereDate('created_at', '>=', date('Y-m-d', $yesterday))->whereDate('created_at', '<=', date('Y-m-d', strtotime($now)))->limit(5)->get();
        $data['shippings'] = Order::select('id', 'code', 'created_at', 'total')->where([['ship', '1'], ['status', '!=', '2']])->whereDate('created_at', '>=', date('Y-m-d', $yesterday))->whereDate('created_at', '<=', date('Y-m-d', strtotime($now)))->limit(5)->get();

        return view('Dashboard::order', $data);
    }

    public function donchuagiao(Request $req)
    {
        $now = date('Y-m-d');
        $time = strtotime('-'.$req->id.' day', strtotime($now));
        if ($req->id == 4) {
            $payments = Order::select('id', 'code', 'created_at', 'total')->where([['ship', '0'], ['status', '!=', '2']])->whereDate('created_at', '<=', date('Y-m-d', $time))->limit(5)->get();
        } else {
            $payments = Order::select('id', 'code', 'created_at', 'total')->where([['ship', '0'], ['status', '!=', '2']])->whereDate('created_at', '>=', date('Y-m-d', $time))->whereDate('created_at', '<=', date('Y-m-d', strtotime($now)))->limit(5)->get();
        }
        if ($payments->count() > 0) {
            foreach ($payments as $payment) {
                echo '<li class="item">
              <div class="product-info ml-0">
                <a href="/admin/order/view/'.$payment->code.'" class="product-title pull-left">#'.$payment->code.'</a>
                <div class="pull-right text-right">
                    <p class="mb-0"><strong>'.number_format($payment->total).'₫</strong></p>
                    <span class="product-description">
                        '.date('d/m/Y H:i:s', strtotime($payment->created_at)).'
                    </span>
                </div>
              </div>
            </li>';
            }
            if ($payments->count() > 5) {
                echo '<a href="/admin/order?stautus=1&ship=0" class="btn btn-default" style="width:100%">Xem thêm <i class="fa fa-angle-right" aria-hidden="true"></i></a>';
            }
        } else {
            echo '<p>Hiện không có đơn hàng nào chưa được xử lý</p>';
        }
    }

    public function danggiao(Request $req)
    {
        $now = date('Y-m-d');
        $time = strtotime('-'.$req->id.' day', strtotime($now));
        if ($req->id == 4) {
            $payments = Order::select('id', 'code', 'created_at', 'total')->where([['ship', '1'], ['status', '!=', '2']])->whereDate('created_at', '<=', date('Y-m-d', $time))->limit(5)->get();
        } else {
            $payments = Order::select('id', 'code', 'created_at', 'total')->where([['ship', '1'], ['status', '!=', '2']])->whereDate('created_at', '>=', date('Y-m-d', $time))->whereDate('created_at', '<=', date('Y-m-d', strtotime($now)))->limit(5)->get();
        }
        if ($payments->count() > 0) {
            foreach ($payments as $payment) {
                echo '<li class="item">
              <div class="product-info ml-0">
                <a href="/admin/order/view/'.$payment->code.'" class="product-title pull-left">#'.$payment->code.'</a>
                <div class="pull-right text-right">
                    <p class="mb-0"><strong>'.number_format($payment->total).'₫</strong></p>
                    <span class="product-description">
                        '.date('d/m/Y H:i:s', strtotime($payment->created_at)).'
                    </span>
                </div>
              </div>
            </li>';
            }
            if ($payments->count() > 5) {
                echo '<a href="/admin/order?stautus=1&ship=1" class="btn btn-default" style="width:100%">Xem thêm <i class="fa fa-angle-right" aria-hidden="true"></i></a>';
            }
        } else {
            echo '<p>Hiện không có đơn hàng nào chưa được xử lý</p>';
        }
    }

    public function chuathanhtoan(Request $req)
    {
        $now = date('Y-m-d');
        $time = strtotime('-'.$req->id.' day', strtotime($now));
        if ($req->id == 4) {
            $payments = Order::select('id', 'code', 'created_at', 'total')->where([['payment', '0'], ['status', '!=', '2']])->whereDate('created_at', '<=', date('Y-m-d', $time))->limit(5)->get();
        } else {
            $payments = Order::select('id', 'code', 'created_at', 'total')->where([['payment', '0'], ['status', '!=', '2']])->whereDate('created_at', '>=', date('Y-m-d', $time))->whereDate('created_at', '<=', date('Y-m-d', strtotime($now)))->limit(5)->get();
        }
        if ($payments->count() > 0) {
            foreach ($payments as $payment) {
                echo '<li class="item">
              <div class="product-info ml-0">
                <a href="/admin/order/view/'.$payment->code.'" class="product-title pull-left">#'.$payment->code.'</a>
                <div class="pull-right text-right">
                    <p class="mb-0"><strong>'.number_format($payment->total).'₫</strong></p>
                    <span class="product-description">
                        '.date('d/m/Y H:i:s',strtotime($payment->created_at)).'
                    </span>
                </div>
              </div>
            </li>';
            }
            if ($payments->count() > 5) {
                echo '<a href="/admin/order?stautus=1&payment=0" class="btn btn-default" style="width:100%">Xem thêm <i class="fa fa-angle-right" aria-hidden="true"></i></a>';
            }
        } else {
            echo '<p>Hiện không có đơn hàng nào chưa được xử lý</p>';
        }
    }
}
