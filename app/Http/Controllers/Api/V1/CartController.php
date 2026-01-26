<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Cart API V1 Controller.
 *
 * Handles cart operations for mobile app
 */
class CartController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Get cart.
     *
     * GET /api/v1/cart
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = auth('member')->id();
            $cart = $this->cartService->getCart($userId);

            return response()->json([
                'success' => true,
                'data' => $cart,
            ]);
        } catch (\Exception $e) {
            Log::error('Get cart failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lấy giỏ hàng thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get cart page data (gio-hang).
     *
     * GET /api/v1/cart/gio-hang
     * Returns full cart page data including sidebar, items, deals, etc.
     */
    public function getCartPage(Request $request): JsonResponse
    {
        try {
            $userId = auth('member')->id();
            $cart = $this->cartService->getCart($userId);

            // Build response similar to cart page structure
            $response = [
                'success' => true,
                'data' => [
                    'items' => $cart['items'] ?? [],
                    'summary' => $cart['summary'] ?? [
                        'total_qty' => 0,
                        'subtotal' => 0,
                        'discount' => 0,
                        'shipping_fee' => 0,
                        'total' => 0,
                    ],
                    'coupon' => $cart['coupon'] ?? null,
                    'available_deals' => $cart['available_deals'] ?? [],
                    'products_with_price' => $cart['products_with_price'] ?? [],
                    'deal_counts' => $cart['deal_counts'] ?? [],
                    'sidebar' => [
                        'title' => 'CỘNG GIỎ HÀNG',
                        'total_price_label' => 'Tổng giá trị đơn hàng',
                        'total_price' => $cart['summary']['subtotal'] ?? 0,
                        'total_price_formatted' => number_format($cart['summary']['subtotal'] ?? 0).'đ',
                        'discount' => $cart['summary']['discount'] ?? 0,
                        'discount_formatted' => $cart['summary']['discount'] > 0
                            ? '-'.number_format($cart['summary']['discount']).'đ'
                            : '0đ',
                        'shipping_fee' => $cart['summary']['shipping_fee'] ?? 0,
                        'shipping_fee_formatted' => number_format($cart['summary']['shipping_fee'] ?? 0).'đ',
                        'total' => $cart['summary']['total'] ?? 0,
                        'total_formatted' => number_format($cart['summary']['total'] ?? 0).'đ',
                        'checkout_url' => '/cart/thanh-toan',
                        'checkout_button_text' => 'Tiến hành thanh toán',
                    ],
                    'checkout_url' => '/cart/thanh-toan',
                    'is_empty' => empty($cart['items']) || count($cart['items']) === 0,
                ],
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Get cart page failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lấy thông tin giỏ hàng thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Add item to cart.
     *
     * POST /api/v1/cart/items
     */
    public function addItem(Request $request): JsonResponse
    {
        try {
            $userId = auth('member')->id();

            // Handle combo (multiple items)
            if ($request->has('combo') && is_array($request->combo)) {
                $totalQty = 0;
                foreach ($request->combo as $item) {
                    $variantId = (int) ($item['variant_id'] ?? $item['id'] ?? 0);
                    $qty = (int) ($item['qty'] ?? 0);
                    $isDeal = isset($item['is_deal']) ? (bool) $item['is_deal'] : false;

                    if ($variantId > 0 && $qty > 0) {
                        $result = $this->cartService->addItem($variantId, $qty, $isDeal, $userId);
                        $totalQty = $result['total_qty'];
                    }
                }

                // Ensure session is saved before returning response
                session()->save();
                \Illuminate\Support\Facades\Session::save();

                return response()->json([
                    'success' => true,
                    'message' => 'Thêm vào giỏ hàng thành công',
                    'data' => [
                        'total_qty' => $totalQty,
                    ],
                ], 201);
            }

            // Single item
            $validator = \Validator::make($request->all(), [
                'variant_id' => 'required|integer|exists:variants,id',
                'qty' => 'required|integer|min:1',
                'is_deal' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors(),
                ], 400);
            }

            // Bước 4: Nhận tham số force_refresh từ query string
            $forceRefresh = (bool) $request->input('force_refresh', false);

            $result = $this->cartService->addItem(
                $request->variant_id,
                $request->qty,
                $request->is_deal ?? false,
                $userId,
                $forceRefresh
            );

            // Ensure session is saved before returning response
            session()->save();
            Session::save();

            return response()->json([
                'success' => true,
                'message' => 'Thêm vào giỏ hàng thành công',
                'data' => $result,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Add to cart failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Thêm vào giỏ hàng thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 400);
        }
    }

    /**
     * Update item quantity.
     *
     * PUT /api/v1/cart/items/{variant_id}
     */
    public function updateItem(Request $request, int $variantId): JsonResponse
    {
        try {
            $validator = \Validator::make($request->all(), [
                'qty' => 'required|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $userId = auth('member')->id();
            $result = $this->cartService->updateItem($variantId, $request->qty, $userId);

            // Ensure session is saved before returning response
            session()->save();
            Session::save();

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Update cart item failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Cập nhật giỏ hàng thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 400);
        }
    }

    /**
     * Remove item from cart.
     *
     * DELETE /api/v1/cart/items/{variant_id}
     */
    public function removeItem(int $variantId): JsonResponse
    {
        try {
            // DEBUG: Log request
            Log::info('[CART API] removeItem request', [
                'variant_id' => $variantId,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'session_id' => session()->getId(),
                'timestamp' => now()->toDateTimeString(),
            ]);

            $userId = auth('member')->id();

            // Get cart state before service call
            $oldCart = Session::has('cart') ? Session::get('cart') : null;
            $cartBefore = $oldCart ? new \App\Themes\Website\Models\Cart($oldCart) : null;

            // DEBUG: Log before service call
            Log::info('[CART API] Before removeItem service call', [
                'variant_id' => $variantId,
                'user_id' => $userId,
                'session_has_cart' => Session::has('cart'),
                'cart_items_count' => $cartBefore ? count($cartBefore->items) : 0,
                'cart_items_keys' => $cartBefore ? array_keys($cartBefore->items) : [],
            ]);

            $result = $this->cartService->removeItem($variantId, $userId);

            // Get cart state after service call
            $cartAfter = Session::has('cart') ? new \App\Themes\Website\Models\Cart(Session::get('cart')) : null;

            // DEBUG: Log after service call
            Log::info('[CART API] After removeItem service call', [
                'variant_id' => $variantId,
                'result' => $result,
                'removed_variant_ids' => $result['removed_variant_ids'] ?? [],
                'removed_count' => count($result['removed_variant_ids'] ?? []),
                'session_has_cart' => Session::has('cart'),
                'cart_items_count_before' => $cartBefore ? count($cartBefore->items) : 0,
                'cart_items_count_after' => $cartAfter ? count($cartAfter->items) : 0,
                'cart_items_keys_before' => $cartBefore ? array_keys($cartBefore->items) : [],
                'cart_items_keys_after' => $cartAfter ? array_keys($cartAfter->items) : [],
            ]);

            // Ensure session is saved before returning response
            session()->save();
            Session::save();

            // DEBUG: Log session save
            Log::info('[CART API] Session saved after removeItem', [
                'variant_id' => $variantId,
                'session_id' => session()->getId(),
            ]);

            // DEBUG: Log response being sent
            Log::info('[CART API] Sending response', [
                'variant_id' => $variantId,
                'response_data' => [
                    'success' => true,
                    'removed_variant_ids' => $result['removed_variant_ids'] ?? [],
                    'summary' => $result['summary'] ?? [],
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Xóa sản phẩm thành công',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            // DEBUG: Log error with full details
            Log::error('[CART API] Remove cart item failed', [
                'variant_id' => $variantId,
                'user_id' => $userId ?? null,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'session_id' => session()->getId(),
                'timestamp' => now()->toDateTimeString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Xóa sản phẩm thất bại',
                'error' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null,
            ], 400);
        }
    }

    /**
     * Apply coupon.
     *
     * POST /api/v1/cart/coupon/apply
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        try {
            $validator = \Validator::make($request->all(), [
                'code' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $userId = auth('member')->id();
            $result = $this->cartService->applyCoupon($request->code, $userId);

            // Ensure session is saved before returning response
            session()->save();
            Session::save();

            return response()->json([
                'success' => true,
                'message' => 'Áp dụng mã thành công',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Apply coupon failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Áp dụng mã thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 400);
        }
    }

    /**
     * Remove coupon.
     *
     * DELETE /api/v1/cart/coupon
     */
    public function removeCoupon(): JsonResponse
    {
        try {
            $userId = auth('member')->id();
            $result = $this->cartService->removeCoupon($userId);

            // Ensure session is saved before returning response
            session()->save();
            Session::save();

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Remove coupon failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Hủy mã thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 400);
        }
    }

    /**
     * Calculate shipping fee.
     *
     * POST /api/v1/cart/shipping-fee
     */
    public function calculateShippingFee(Request $request): JsonResponse
    {
        try {
            $validator = \Validator::make($request->all(), [
                'province_id' => 'required|integer',
                'district_id' => 'required|integer',
                'ward_id' => 'required|integer',
                'address' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $userId = auth('member')->id();
            $shippingFee = $this->cartService->calculateShippingFee([
                'province_id' => $request->province_id,
                'district_id' => $request->district_id,
                'ward_id' => $request->ward_id,
                'address' => $request->address ?? '',
            ], $userId);

            // Get cart summary
            $cart = $this->cartService->getCart($userId);
            $discount = $cart['summary']['discount'] ?? 0;
            $subtotal = $cart['summary']['subtotal'] ?? 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'shipping_fee' => (float) $shippingFee,
                    'free_ship' => $shippingFee == 0,
                    'summary' => [
                        'subtotal' => $subtotal,
                        'discount' => $discount,
                        'shipping_fee' => (float) $shippingFee,
                        'total' => (float) ($subtotal - $discount + $shippingFee),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Calculate shipping fee failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Tính phí vận chuyển thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 400);
        }
    }

    /**
     * Checkout.
     *
     * POST /api/v1/cart/checkout
     */
    public function checkout(Request $request): JsonResponse
    {
        try {
            $validator = \Validator::make($request->all(), [
                'full_name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'required|string',
                'province_id' => 'required|integer',
                'district_id' => 'required|integer',
                'ward_id' => 'required|integer',
                'remark' => 'nullable|string',
                'shipping_fee' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng điền đầy đủ thông tin giao hàng',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $userId = auth('member')->id();
            $result = $this->cartService->checkout([
                'full_name' => $request->full_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'province_id' => $request->province_id,
                'district_id' => $request->district_id,
                'ward_id' => $request->ward_id,
                'remark' => $request->remark,
                'shipping_fee' => $request->shipping_fee ?? 0,
            ], $userId);

            return response()->json([
                'success' => true,
                'message' => 'Đặt hàng thành công',
                'data' => $result,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Checkout failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Đặt hàng thất bại',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 400);
        }
    }
}
