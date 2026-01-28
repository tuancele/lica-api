<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Order\OrderDetailResource;
use App\Http\Resources\Order\UserOrderResource;
use App\Modules\Order\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Order\OrderServiceInterface;

/**
 * Order API V1 Controller.
 *
 * Handles order operations for authenticated users (members)
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly OrderServiceInterface $orders
    ) {
    }
    /**
     * Get user orders list.
     *
     * GET /api/v1/orders
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $member = auth('member')->user();

            if (! $member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            // Pagination (service side still uses admin filters; here we only need member scoping)
            $perPage = min((int) ($request->limit ?? 10), 50);
            $orders = Order::with(['province', 'district', 'ward', 'promotion'])
                ->where(function ($q) use ($member) {
                    $q->where('member_id', $member->id)
                        ->orWhere('user_id', $member->id);
                })
                ->when($request->has('status') && $request->status !== '', function ($q) use ($request) {
                    $q->where('status', $request->status);
                })
                ->when($request->has('payment') && $request->payment !== '', function ($q) use ($request) {
                    $q->where('payment', $request->payment);
                })
                ->when($request->has('ship') && $request->ship !== '', function ($q) use ($request) {
                    $q->where('ship', $request->ship);
                })
                ->when($request->has('date_from') && ! empty($request->date_from), function ($q) use ($request) {
                    $q->whereDate('created_at', '>=', $request->date_from);
                })
                ->when($request->has('date_to') && ! empty($request->date_to), function ($q) use ($request) {
                    $q->whereDate('created_at', '<=', $request->date_to);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

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
            Log::error('Get user orders list failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách đơn hàng thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get order detail.
     *
     * GET /api/v1/orders/{code}
     */
    public function show(string $code): JsonResponse
    {
        try {
            $member = auth('member')->user();

            if (! $member) {
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
                ->where(function ($q) use ($member) {
                    $q->where('member_id', $member->id)
                        ->orWhere('user_id', $member->id);
                })
                ->first();

            if (! $order) {
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
            Log::error('Get order detail failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lấy chi tiết đơn hàng thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
