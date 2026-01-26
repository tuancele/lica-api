<?php

declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Order\OrderResource;
use App\Http\Resources\Order\OrderDetailResource;
use App\Http\Resources\Order\UserOrderResource;
use App\Modules\Order\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Order API V1 Controller
 * 
 * Handles order operations for authenticated users (members)
 */
class OrderController extends Controller
{
    /**
     * Get user orders list
     * 
     * GET /api/v1/orders
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $member = auth('member')->user();
            
            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }
            
            // Support both member_id and user_id for compatibility
            $query = Order::with(['province', 'district', 'ward', 'promotion'])
                ->where(function($q) use ($member) {
                    $q->where('member_id', $member->id)
                      ->orWhere('user_id', $member->id);
                });
            
            // Filter by status
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }
            
            // Filter by payment status
            if ($request->has('payment') && $request->payment !== '') {
                $query->where('payment', $request->payment);
            }
            
            // Filter by ship status
            if ($request->has('ship') && $request->ship !== '') {
                $query->where('ship', $request->ship);
            }
            
            // Filter by date
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
            
            // Pagination
            $perPage = min((int)($request->limit ?? 10), 50);
            $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => UserOrderResource::collection($orders),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get user orders list failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách đơn hàng thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get order detail
     * 
     * GET /api/v1/orders/{code}
     */
    public function show(string $code): JsonResponse
    {
        try {
            $member = auth('member')->user();
            
            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }
            
            // Support both member_id and user_id for compatibility
            $order = Order::with([
                'province',
                'district',
                'ward',
                'promotion',
                'detail.variant',
                'detail.product',
                'detail.color',
                'detail.size',
            ])
            ->where('code', $code)
            ->where(function($q) use ($member) {
                $q->where('member_id', $member->id)
                  ->orWhere('user_id', $member->id);
            })
            ->first();
            
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đơn hàng không tồn tại hoặc không thuộc về bạn',
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => new OrderDetailResource($order),
            ]);
        } catch (\Exception $e) {
            Log::error('Get order detail failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lấy chi tiết đơn hàng thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
