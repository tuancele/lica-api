<?php

declare(strict_types=1);

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Order\OrderDetailResource;
use App\Http\Resources\Order\OrderResource;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderDetail;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Order Management API Controller (Admin).
 */
class OrderController extends Controller
{
    /**
     * Get orders list.
     *
     * GET /admin/api/orders
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Order::with(['province', 'district', 'ward', 'promotion', 'member']);

            // Filter by status
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }

            // Search by keyword
            if ($request->has('keyword') && ! empty($request->keyword)) {
                $keyword = $request->keyword;
                $query->where(function ($q) use ($keyword) {
                    $q->where('code', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%");
                });
            }

            // Filter by date
            if ($request->has('date_from') && ! empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to') && ! empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Filter by payment status
            if ($request->has('payment') && $request->payment !== '') {
                $query->where('payment', $request->payment);
            }

            // Filter by ship status
            if ($request->has('ship') && $request->ship !== '') {
                $query->where('ship', $request->ship);
            }

            // Filter by user_id
            if ($request->has('user_id') && $request->user_id !== '') {
                $query->where('user_id', $request->user_id);
            }

            // Pagination
            $perPage = min((int) ($request->limit ?? 10), 100);
            $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => OrderResource::collection($orders),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get orders list failed: '.$e->getMessage());

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
     * GET /admin/api/orders/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $order = Order::with([
                'province',
                'district',
                'ward',
                'promotion',
                'member',
                'detail.variant',
                'detail.product',
                'detail.color',
                'detail.size',
            ])->find($id);

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đơn hàng không tồn tại',
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

    /**
     * Update order status.
     *
     * PATCH /admin/api/orders/{id}/status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = \Validator::make($request->all(), [
                'status' => 'required|string|in:0,1,2,3,4',
                'payment' => 'sometimes|string|in:0,1,2',
                'ship' => 'sometimes|string|in:0,1,2,3,4',
                'content' => 'sometimes|string|nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $order = Order::with('detail')->find($id);
            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đơn hàng không tồn tại',
                ], 404);
            }

            $oldStatus = $order->status;
            $newStatus = $request->status;

            // Process stock updates in transaction
            DB::transaction(function () use ($order, $oldStatus, $newStatus, $request) {
                // If changing from non-cancelled to cancelled (status = 4)
                if ($oldStatus != '4' && $newStatus == '4') {
                    // Restore stock for all items
                    foreach ($order->detail as $detail) {
                        if ($detail->variant_id) {
                            Variant::where('id', $detail->variant_id)
                                ->lockForUpdate()
                                ->increment('stock', $detail->qty);
                        } else {
                            Product::where('id', $detail->product_id)
                                ->lockForUpdate()
                                ->increment('stock', $detail->qty);
                        }
                    }
                }

                // If changing from cancelled (status = 4) to non-cancelled
                if ($oldStatus == '4' && $newStatus != '4') {
                    // Deduct stock again
                    foreach ($order->detail as $detail) {
                        if ($detail->variant_id) {
                            $variant = Variant::where('id', $detail->variant_id)->lockForUpdate()->first();
                            if ($variant && $variant->stock >= $detail->qty) {
                                $variant->decrement('stock', $detail->qty);
                            } else {
                                throw new \Exception("Không đủ tồn kho cho sản phẩm: {$detail->name}");
                            }
                        } else {
                            $product = Product::where('id', $detail->product_id)->lockForUpdate()->first();
                            if ($product && $product->stock >= $detail->qty) {
                                $product->decrement('stock', $detail->qty);
                            } else {
                                throw new \Exception("Không đủ tồn kho cho sản phẩm: {$detail->name}");
                            }
                        }
                    }
                }

                // Update order
                $order->update([
                    'status' => $newStatus,
                    'payment' => $request->has('payment') ? $request->payment : $order->payment,
                    'ship' => $request->has('ship') ? $request->ship : $order->ship,
                    'content' => $request->has('content') ? $request->content : $order->content,
                    'user_id' => Auth::id(),
                ]);
            });

            // Reload order with relationships
            $order->load(['province', 'district', 'ward', 'promotion', 'member', 'detail.variant', 'detail.product', 'detail.color', 'detail.size']);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công',
                'data' => new OrderDetailResource($order),
            ]);
        } catch (\Exception $e) {
            Log::error('Update order status failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Cập nhật trạng thái thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update order.
     *
     * PUT /admin/api/orders/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validator = \Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'phone' => 'sometimes|string|max:20|nullable',
                'email' => 'sometimes|email|max:255|nullable',
                'address' => 'sometimes|string|max:500|nullable',
                'provinceid' => 'sometimes|integer|nullable',
                'districtid' => 'sometimes|integer|nullable',
                'wardid' => 'sometimes|integer|nullable',
                'remark' => 'sometimes|string|nullable',
                'content' => 'sometimes|string|nullable',
                'fee_ship' => 'sometimes|numeric|min:0',
                'items' => 'sometimes|array',
                'items.*.id' => 'sometimes|integer|exists:orderdetail,id',
                'items.*.product_id' => 'required_without:items.*.id|integer|exists:posts,id',
                'items.*.variant_id' => 'sometimes|integer|nullable|exists:variants,id',
                'items.*.qty' => 'required_without:items.*.id|integer|min:1',
                'items.*.price' => 'sometimes|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $order = Order::with('detail')->find($id);
            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đơn hàng không tồn tại',
                ], 404);
            }

            // Check if order is cancelled - cannot update cancelled orders
            if ($order->status == '4') {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể chỉnh sửa đơn hàng đã hủy',
                ], 400);
            }

            // Process update in transaction
            DB::transaction(function () use ($order, $request) {
                // Update customer and shipping info
                $order->update([
                    'name' => $request->has('name') ? $request->name : $order->name,
                    'phone' => $request->has('phone') ? $request->phone : $order->phone,
                    'email' => $request->has('email') ? $request->email : $order->email,
                    'address' => $request->has('address') ? $request->address : $order->address,
                    'provinceid' => $request->has('provinceid') ? $request->provinceid : $order->provinceid,
                    'districtid' => $request->has('districtid') ? $request->districtid : $order->districtid,
                    'wardid' => $request->has('wardid') ? $request->wardid : $order->wardid,
                    'remark' => $request->has('remark') ? $request->remark : $order->remark,
                    'content' => $request->has('content') ? $request->content : $order->content,
                    'fee_ship' => $request->has('fee_ship') ? $request->fee_ship : $order->fee_ship,
                    'user_id' => Auth::id(),
                ]);

                // Process items update if provided
                if ($request->has('items')) {
                    $existingDetailIds = [];

                    foreach ($request->items as $item) {
                        if (isset($item['id'])) {
                            // Update existing orderdetail
                            $detail = OrderDetail::where('id', $item['id'])
                                ->where('order_id', $order->id)
                                ->first();

                            if ($detail) {
                                $oldQty = $detail->qty;
                                $newQty = $item['qty'];
                                $qtyDiff = $newQty - $oldQty;

                                // Update stock if quantity changed
                                if ($qtyDiff != 0) {
                                    if ($detail->variant_id) {
                                        $variant = Variant::where('id', $detail->variant_id)->lockForUpdate()->first();
                                        if ($variant) {
                                            if ($qtyDiff > 0 && $variant->stock < $qtyDiff) {
                                                throw new \Exception("Không đủ tồn kho cho sản phẩm: {$detail->name}");
                                            }
                                            $variant->increment('stock', -$qtyDiff);
                                        }
                                    } else {
                                        $product = Product::where('id', $detail->product_id)->lockForUpdate()->first();
                                        if ($product) {
                                            if ($qtyDiff > 0 && $product->stock < $qtyDiff) {
                                                throw new \Exception("Không đủ tồn kho cho sản phẩm: {$detail->name}");
                                            }
                                            $product->increment('stock', -$qtyDiff);
                                        }
                                    }
                                }

                                // Update orderdetail
                                $price = isset($item['price']) ? $item['price'] : $detail->price;
                                $detail->update([
                                    'qty' => $newQty,
                                    'price' => $price,
                                    'subtotal' => $price * $newQty,
                                ]);

                                $existingDetailIds[] = $detail->id;
                            }
                        } else {
                            // Add new orderdetail
                            $product = Product::find($item['product_id']);
                            if (! $product) {
                                continue;
                            }

                            $variant = isset($item['variant_id']) ? Variant::find($item['variant_id']) : null;

                            // Get price
                            $price = isset($item['price'])
                                ? $item['price']
                                : ($variant ? ($variant->sale ?? $variant->price) : ($product->sale ?? $product->price));

                            $qty = $item['qty'];

                            // Check and deduct stock
                            if ($variant) {
                                $variantLocked = Variant::where('id', $variant->id)->lockForUpdate()->first();
                                if (! $variantLocked || $variantLocked->stock < $qty) {
                                    throw new \Exception("Không đủ tồn kho cho sản phẩm: {$product->name}");
                                }
                                $variantLocked->decrement('stock', $qty);
                            } else {
                                $productLocked = Product::where('id', $product->id)->lockForUpdate()->first();
                                if (! $productLocked || $productLocked->stock < $qty) {
                                    throw new \Exception("Không đủ tồn kho cho sản phẩm: {$product->name}");
                                }
                                $productLocked->decrement('stock', $qty);
                            }

                            // Create orderdetail
                            OrderDetail::create([
                                'order_id' => $order->id,
                                'product_id' => $product->id,
                                'variant_id' => $variant->id ?? null,
                                'name' => $product->name,
                                'image' => $variant->image ?? $product->image,
                                'price' => $price,
                                'qty' => $qty,
                                'subtotal' => $price * $qty,
                                'weight' => $variant->weight ?? $product->weight ?? 0,
                                'color_id' => $variant->color_id ?? null,
                                'size_id' => $variant->size_id ?? null,
                            ]);
                        }
                    }

                    // Delete orderdetails not in request
                    $detailsToDelete = OrderDetail::where('order_id', $order->id)
                        ->whereNotIn('id', $existingDetailIds)
                        ->get();

                    foreach ($detailsToDelete as $detail) {
                        // Restore stock
                        if ($detail->variant_id) {
                            Variant::where('id', $detail->variant_id)
                                ->lockForUpdate()
                                ->increment('stock', $detail->qty);
                        } else {
                            Product::where('id', $detail->product_id)
                                ->lockForUpdate()
                                ->increment('stock', $detail->qty);
                        }

                        $detail->delete();
                    }
                }

                // Recalculate total
                $order->total = OrderDetail::where('order_id', $order->id)->sum('subtotal');
                $order->save();
            });

            // Reload order with relationships
            $order->load(['province', 'district', 'ward', 'promotion', 'member', 'detail.variant', 'detail.product', 'detail.color', 'detail.size']);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật đơn hàng thành công',
                'data' => new OrderDetailResource($order),
            ]);
        } catch (\Exception $e) {
            Log::error('Update order failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Cập nhật đơn hàng thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get status label.
     */
    private function getStatusLabel(?string $status): string
    {
        $labels = [
            '0' => 'Chờ xử lý',
            '1' => 'Đã xác nhận',
            '2' => 'Đã giao hàng',
            '3' => 'Hoàn thành',
            '4' => 'Đã hủy',
        ];

        return $labels[$status] ?? 'Không xác định';
    }

    /**
     * Format image URL.
     */
    private function formatImageUrl(?string $image): string
    {
        if (empty($image)) {
            $r2Domain = config('filesystems.disks.r2.url', '');
            if (! empty($r2Domain)) {
                return rtrim($r2Domain, '/').'/public/image/no_image.png';
            }

            return asset('/public/image/no_image.png');
        }

        $r2Domain = config('filesystems.disks.r2.url', '');
        $r2DomainClean = ! empty($r2Domain) ? rtrim($r2Domain, '/') : '';

        if (empty($r2DomainClean)) {
            return filter_var($image, FILTER_VALIDATE_URL) ? $image : asset($image);
        }

        $image = trim($image);
        $checkR2 = str_replace(['http://', 'https://'], '', $r2DomainClean);
        $cleanPath = str_replace(['http://', 'https://'], '', $image);
        $cleanPath = str_replace($checkR2.'/', '', $cleanPath);
        $cleanPath = str_replace($checkR2, '', $cleanPath);
        $cleanPath = preg_replace('#/+#', '/', $cleanPath);
        $cleanPath = preg_replace('#(uploads/)+#', 'uploads/', $cleanPath);
        $cleanPath = ltrim($cleanPath, '/');

        return $r2DomainClean.'/'.$cleanPath;
    }
}
