<?php

namespace App\Themes\Website\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Cart\CartService;
use App\Services\Pricing\PriceEngineServiceInterface;
use App\Services\Warehouse\WarehouseServiceInterface;
use App\Modules\Product\Models\Variant;
use App\Themes\Website\Models\Cart;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CartControllerV2 extends Controller
{
    protected CartService $cartService;
    protected PriceEngineServiceInterface $priceEngine;
    protected WarehouseServiceInterface $warehouseService;

    public function __construct(
        CartService $cartService,
        PriceEngineServiceInterface $priceEngine,
        WarehouseServiceInterface $warehouseService
    ) {
        $this->cartService = $cartService;
        $this->priceEngine = $priceEngine;
        $this->warehouseService = $warehouseService;

        if (method_exists($this->priceEngine, 'setWarehouseService')) {
            $this->priceEngine->setWarehouseService($warehouseService);
        }
    }

    public function index()
    {
        try {
            $userId = auth('member')->id();
            Log::info('[CartV2] index called', [
                'user_id' => $userId,
                'session_id' => Session::getId(),
                'has_cart' => Session::has('cart'),
            ]);

            $cartData = $this->cartService->getCart($userId);

            Log::info('[CartV2] index cart data loaded', [
                'items_count' => count($cartData['items'] ?? []),
                'subtotal' => $cartData['summary']['subtotal'] ?? 0,
                'total' => $cartData['summary']['total'] ?? 0,
            ]);

            return view('Website::cart.v2.index', [
                'cart' => $cartData,
            ]);
        } catch (\Exception $e) {
            Log::error('[CartV2] Index failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect('/')->with('error', 'Không thể tải giỏ hàng');
        }
    }

    public function addItem(Request $request)
    {
        try {
            Log::info('[CartV2] addItem called', [
                'request_data' => $request->all(),
                'user_id' => auth('member')->id(),
                'session_id' => Session::getId(),
            ]);

            $validator = Validator::make($request->all(), [
                'variant_id' => 'required|integer|exists:variants,id',
                'qty' => 'required|integer|min:1',
                'is_deal' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                Log::warning('[CartV2] addItem validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'request_data' => $request->all(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $userId = auth('member')->id();
            $result = $this->cartService->addItem(
                $request->variant_id,
                $request->qty,
                $request->is_deal ?? false,
                $userId
            );

            session()->save();

            Log::info('[CartV2] addItem success', [
                'variant_id' => $request->variant_id,
                'qty' => $request->qty,
                'total_qty' => $result['total_qty'] ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thêm vào giỏ hàng thành công',
                'data' => $result,
            ], 201);
        } catch (\Exception $e) {
            Log::error('[CartV2] Add item failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Thêm vào giỏ hàng thất bại',
            ], 400);
        }
    }

    public function updateItem(Request $request, int $variantId)
    {
        try {
            Log::info('[CartV2] updateItem called', [
                'variant_id' => $variantId,
                'qty' => $request->qty,
                'user_id' => auth('member')->id(),
                'session_id' => Session::getId(),
            ]);

            $validator = Validator::make($request->all(), [
                'qty' => 'required|integer|min:0',
            ]);

            if ($validator->fails()) {
                Log::warning('[CartV2] updateItem validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'variant_id' => $variantId,
                    'qty' => $request->qty,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $userId = auth('member')->id();
            $result = $this->cartService->updateItem($variantId, $request->qty, $userId);

            session()->save();

            Log::info('[CartV2] updateItem success', [
                'variant_id' => $variantId,
                'qty' => $request->qty,
                'total_qty' => $result['summary']['total_qty'] ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('[CartV2] Update item failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'variant_id' => $variantId,
                'qty' => $request->qty ?? null,
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Cập nhật giỏ hàng thất bại',
            ], 400);
        }
    }

    public function removeItem(int $variantId)
    {
        try {
            $userId = auth('member')->id();
            Log::info('[CartV2] removeItem called', [
                'variant_id' => $variantId,
                'user_id' => $userId,
                'session_id' => Session::getId(),
            ]);

            $result = $this->cartService->removeItem($variantId, $userId);

            session()->save();

            Log::info('[CartV2] removeItem success', [
                'variant_id' => $variantId,
                'total_qty' => $result['summary']['total_qty'] ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Xóa sản phẩm thành công',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('[CartV2] Remove item failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'variant_id' => $variantId,
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Xóa sản phẩm thất bại',
            ], 400);
        }
    }

    public function getCartJson()
    {
        try {
            $userId = auth('member')->id();
            Log::info('[CartV2] getCartJson called', [
                'user_id' => $userId,
                'session_id' => Session::getId(),
            ]);

            $cart = $this->cartService->getCart($userId);

            Log::info('[CartV2] getCartJson success', [
                'items_count' => count($cart['items'] ?? []),
                'subtotal' => $cart['summary']['subtotal'] ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'data' => $cart,
            ]);
        } catch (\Exception $e) {
            Log::error('[CartV2] Get cart JSON failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Lấy thông tin giỏ hàng thất bại',
            ], 500);
        }
    }

    public function get()
    {
        try {
            $oldCart = Session::has('cart') ? Session::get('cart') : null;
            $cart = new Cart($oldCart);
            
            $products = $cart->items ?? [];
            $totalPrice = 0;
            
            foreach ($products as $variantId => $item) {
                $variant = Variant::with(['product', 'color', 'size'])->find($variantId);
                if (!$variant || !$variant->product) {
                    continue;
                }
                
                $quantity = (int)($item['qty'] ?? 1);
                $priceWithQuantity = $this->priceEngine->calculatePriceWithQuantity(
                    $variant->product->id,
                    $variantId,
                    $quantity
                );
                
                $totalPrice += $priceWithQuantity['total_price'];
            }

            return view('Website::cart.get', [
                'products' => $products,
                'totalPrice' => $totalPrice,
            ]);
        } catch (\Exception $e) {
            Log::error('[CartV2] Get failed: ' . $e->getMessage());
            return view('Website::cart.get', [
                'products' => [],
                'totalPrice' => 0,
            ]);
        }
    }

    public function addCart(Request $request)
    {
        try {
            $userId = auth('member')->id();
            Log::info('[CartV2] addCart called', [
                'request_data' => $request->all(),
                'user_id' => $userId,
                'session_id' => Session::getId(),
                'has_combo' => $request->has('combo'),
            ]);
            
            if ($request->has('combo') && is_array($request->combo)) {
                $totalQty = 0;
                foreach ($request->combo as $item) {
                    $variantId = (int)($item['id'] ?? $item['variant_id'] ?? 0);
                    $qty = (int)($item['qty'] ?? 0);
                    $isDeal = isset($item['is_deal']) ? (bool)$item['is_deal'] : false;
                    
                    if ($variantId > 0 && $qty > 0) {
                        try {
                            Log::info('[CartV2] addCart combo item', [
                                'variant_id' => $variantId,
                                'qty' => $qty,
                                'is_deal' => $isDeal,
                            ]);
                            $result = $this->cartService->addItem($variantId, $qty, $isDeal, $userId);
                            $totalQty = $result['total_qty'] ?? $totalQty;
                        } catch (\Exception $e) {
                            Log::error('[CartV2] AddCart combo item failed', [
                                'message' => $e->getMessage(),
                                'file' => $e->getFile(),
                                'line' => $e->getLine(),
                                'variant_id' => $variantId,
                                'qty' => $qty,
                                'is_deal' => $isDeal,
                            ]);
                            return response()->json([
                                'status' => 'error',
                                'message' => $e->getMessage() ?: 'Thêm sản phẩm vào giỏ hàng thất bại',
                            ], 400);
                        }
                    }
                }
                
                session()->save();
                
                Log::info('[CartV2] addCart combo success', [
                    'total_qty' => $totalQty,
                ]);
                
                return response()->json([
                    'status' => 'success',
                    'total' => $totalQty,
                ]);
            }
            
            $variantId = (int)($request->variant_id ?? $request->id ?? 0);
            $qty = (int)($request->qty ?? 1);
            $isDeal = isset($request->is_deal) ? (bool)$request->is_deal : false;
            
            Log::info('[CartV2] addCart single item', [
                'variant_id' => $variantId,
                'qty' => $qty,
                'is_deal' => $isDeal,
            ]);
            
            if ($variantId <= 0) {
                Log::warning('[CartV2] addCart missing variant_id', [
                    'request_data' => $request->all(),
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Thiếu thông tin sản phẩm',
                ], 400);
            }
            
            if ($qty <= 0) {
                Log::warning('[CartV2] addCart invalid qty', [
                    'qty' => $qty,
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Số lượng không hợp lệ',
                ], 400);
            }

            try {
                $result = $this->cartService->addItem($variantId, $qty, $isDeal, $userId);
                session()->save();
                
                $variant = Variant::with('product')->find($variantId);
                $productName = $variant && $variant->product ? $variant->product->name : 'Sản phẩm';
                
                Log::info('[CartV2] addCart success', [
                    'variant_id' => $variantId,
                    'qty' => $qty,
                    'product_name' => $productName,
                    'total_qty' => $result['total_qty'] ?? 0,
                ]);
                
                return response()->json([
                    'status' => 'success',
                    'name' => $productName,
                    'total' => $result['total_qty'] ?? 0,
                ]);
            } catch (\Exception $e) {
                Log::error('[CartV2] AddCart failed', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'variant_id' => $variantId,
                    'qty' => $qty,
                    'is_deal' => $isDeal,
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage() ?: 'Thêm vào giỏ hàng thất bại',
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('[CartV2] AddCart exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Thêm vào giỏ hàng thất bại',
            ], 400);
        }
    }

    public function delCart(Request $request)
    {
        try {
            $userId = auth('member')->id();
            $variantId = (int)($request->id ?? 0);
            
            Log::info('[CartV2] delCart called', [
                'variant_id' => $variantId,
                'user_id' => $userId,
                'session_id' => Session::getId(),
                'request_data' => $request->all(),
            ]);
            
            if ($variantId <= 0) {
                Log::warning('[CartV2] delCart invalid variant_id', [
                    'variant_id' => $variantId,
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'ID không hợp lệ',
                ], 400);
            }
            return $this->removeItem($variantId);
        } catch (\Exception $e) {
            Log::error('[CartV2] delCart exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Xóa sản phẩm thất bại',
            ], 400);
        }
    }

    public function updateCart(Request $request)
    {
        try {
            $userId = auth('member')->id();
            $variantId = (int)($request->id ?? 0);
            $qty = (int)($request->qty ?? 0);
            
            Log::info('[CartV2] updateCart called', [
                'variant_id' => $variantId,
                'qty' => $qty,
                'user_id' => $userId,
                'session_id' => Session::getId(),
                'request_data' => $request->all(),
            ]);
            
            if ($variantId <= 0) {
                Log::warning('[CartV2] updateCart invalid variant_id', [
                    'variant_id' => $variantId,
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'ID không hợp lệ',
                ], 400);
            }

            $request->merge(['qty' => $qty]);
            $result = $this->updateItem($request, $variantId);
            
            if ($result->getStatusCode() === 200) {
                $data = json_decode($result->getContent(), true);
                $oldCart = Session::has('cart') ? Session::get('cart') : null;
                $cart = new Cart($oldCart);
                $sale = Session::has('ss_counpon') ? Session::get('ss_counpon')['sale'] : 0;
                
                Log::info('[CartV2] updateCart success', [
                    'variant_id' => $variantId,
                    'qty' => $qty,
                    'total_qty' => $cart->totalQty,
                    'total_price' => $cart->totalPrice,
                ]);
                
                return response()->json([
                    'total' => $cart->totalQty,
                    'price' => number_format($cart->totalPrice),
                    'subtotal' => isset($cart->items[$variantId]) ? number_format($cart->items[$variantId]['price'] * $qty) : 0,
                    'totalPrice' => number_format($cart->totalPrice - $sale),
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('[CartV2] updateCart exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Cập nhật giỏ hàng thất bại',
            ], 400);
        }
    }
}

