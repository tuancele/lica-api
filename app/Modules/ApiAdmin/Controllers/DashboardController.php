<?php

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Post\Models\Post;
use App\Modules\Contact\Models\Contact;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function statistics(): JsonResponse
    {
        try {
            $productCount = Post::where('type', 'product')->count();
            $postCount = Post::where('type', 'post')->count();
            $contactCount = Contact::count();
            $orders = Order::where('status', '!=', '2')->get();
            $totalRevenue = $orders->sum('total');
            $totalOrders = $orders->count();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $productCount,
                    'posts' => $postCount,
                    'contacts' => $contactCount,
                    'orders' => $totalOrders,
                    'revenue' => $totalRevenue,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get dashboard statistics failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get dashboard statistics'], 500);
        }
    }

    public function charts(Request $request): JsonResponse
    {
        try {
            $start = $request->get('date_from', strtotime('-1 day', strtotime(date('Y-m-d'))));
            $end = $request->get('date_to', strtotime(date('Y-m-d')));
            
            if (is_string($start)) $start = strtotime($start);
            if (is_string($end)) $end = strtotime($end);
            
            $statistics = [];
            for ($i = $start; $i <= $end; $i = $i + 86400) {
                $date = date('Y-m-d', $i);
                $orders = Order::where('status', '!=', '2')
                    ->whereDate('created_at', $date)
                    ->get();
                
                $statistics[] = [
                    'date' => $date,
                    'revenue' => $orders->sum('total'),
                    'orders_count' => $orders->count(),
                ];
            }
            
            $topProducts = DB::table('orderdetail')
                ->select('product_id', DB::raw('SUM(qty) AS total_qty'))
                ->whereDate('created_at', '>=', date('Y-m-d', $start))
                ->whereDate('created_at', '<=', date('Y-m-d', $end))
                ->groupBy('product_id')
                ->orderBy('total_qty', 'desc')
                ->limit(5)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $statistics,
                    'top_products' => $topProducts,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get dashboard charts failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get dashboard charts'], 500);
        }
    }

    public function recentOrders(): JsonResponse
    {
        try {
            $orders = Order::where('status', '!=', '2')
                ->select('id', 'code', 'total', 'created_at', 'status')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $orders,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get recent orders failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get recent orders'], 500);
        }
    }

    public function topProducts(): JsonResponse
    {
        try {
            $topProducts = DB::table('orderdetail')
                ->join('products', 'orderdetail.product_id', '=', 'products.id')
                ->select('products.id', 'products.name', DB::raw('SUM(orderdetail.qty) AS total_qty'))
                ->groupBy('products.id', 'products.name')
                ->orderBy('total_qty', 'desc')
                ->limit(10)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $topProducts,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get top products failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get top products'], 500);
        }
    }
}

