<?php

namespace App\Http\Controllers;

use App\Services\Pricing\PriceEngineServiceInterface;
use App\Services\Inventory\InventoryServiceInterface;
use App\Services\Warehouse\WarehouseServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * Order Processing Controller
 * 
 * Xử lý đơn hàng với logic tính giá và quản lý tồn kho
 */
class OrderProcessingController extends Controller
{
    protected PriceEngineServiceInterface $priceEngine;
    protected InventoryServiceInterface $inventoryService;
    protected WarehouseServiceInterface $warehouseService;
    
    public function __construct(
        PriceEngineServiceInterface $priceEngine,
        InventoryServiceInterface $inventoryService,
        WarehouseServiceInterface $warehouseService
    ) {
        $this->priceEngine = $priceEngine;
        $this->inventoryService = $inventoryService;
        $this->warehouseService = $warehouseService;
        
        // Inject WarehouseService vào PriceEngineService để kiểm tra tồn kho
        if (method_exists($this->priceEngine, 'setWarehouseService')) {
            $this->priceEngine->setWarehouseService($warehouseService);
        }
    }
    
    /**
     * Tính giá hiển thị cho sản phẩm
     * GET /api/price/{productId}
     * 
     * @param Request $request
     * @param int $productId
     * @return JsonResponse
     */
    public function getPrice(Request $request, int $productId): JsonResponse
    {
        try {
            $variantId = $request->get('variant_id');
            
            $priceInfo = $this->priceEngine->calculateDisplayPrice($productId, $variantId);
            
            return response()->json([
                'success' => true,
                'data' => $priceInfo,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get Price Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi tính giá: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Tính giá với số lượng (hỗ trợ giá hỗn hợp khi mua vượt hạn mức)
     * POST /api/price/calculate
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function calculatePrice(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:posts,id',
                'variant_id' => 'nullable|exists:variants,id',
                'quantity' => 'required|integer|min:1',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            $priceInfo = $this->priceEngine->calculatePriceWithQuantity(
                $request->product_id,
                $request->variant_id ?? null,
                $request->quantity
            );
            
            return response()->json([
                'success' => true,
                'data' => $priceInfo,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Calculate Price Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi tính giá: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Xử lý đơn hàng
     * POST /api/orders/process
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function processOrder(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:posts,id',
                'items.*.variant_id' => 'nullable|exists:variants,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.order_type' => 'required|in:flashsale,promotion,normal',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            // Tính giá cho từng sản phẩm và xác định order_type
            $orderItems = [];
            $warnings = [];
            
            foreach ($request->items as $index => $item) {
                // Tính giá với số lượng để kiểm tra cảnh báo
                $priceWithQuantity = $this->priceEngine->calculatePriceWithQuantity(
                    $item['product_id'],
                    $item['variant_id'] ?? null,
                    $item['quantity']
                );
                
                // Tính giá hiển thị
                $priceInfo = $this->priceEngine->calculateDisplayPrice(
                    $item['product_id'],
                    $item['variant_id'] ?? null
                );
                
                // Xác định order_type dựa trên giá hiện tại
                $orderType = $priceInfo['type'] === 'flashsale' ? 'flashsale' : 'normal';
                
                // Kiểm tra cảnh báo khi mua vượt hạn mức Flash Sale
                if ($orderType === 'flashsale' && $priceWithQuantity['warning']) {
                    $warnings[] = [
                        'item_index' => $index,
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'] ?? null,
                        'message' => $priceWithQuantity['warning'],
                        'flash_remaining' => $priceWithQuantity['flash_sale_remaining'],
                    ];
                }
                
                $orderItems[] = [
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'order_type' => $orderType,
                    'price_info' => $priceInfo,
                    'price_with_quantity' => $priceWithQuantity,
                ];
            }
            
            // Xử lý đơn hàng và trừ tồn kho
            $result = $this->inventoryService->processOrder($orderItems);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }
            
            // Thu thập cảnh báo từ kết quả xử lý
            $processedWarnings = [];
            if (isset($result['warnings']) && is_array($result['warnings'])) {
                $processedWarnings = $result['warnings'];
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Xử lý đơn hàng thành công',
                'data' => [
                    'items' => $orderItems,
                    'flash_sale_exhausted' => collect($orderItems)->contains(function($item) use ($result) {
                        return isset($result['flash_sale_exhausted']) && $result['flash_sale_exhausted'];
                    }),
                    'warnings' => array_merge($warnings, $processedWarnings),
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Process Order Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xử lý đơn hàng: ' . $e->getMessage(),
            ], 500);
        }
    }
}
