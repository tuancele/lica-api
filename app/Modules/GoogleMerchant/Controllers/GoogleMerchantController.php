<?php

declare(strict_types=1);
namespace App\Modules\GoogleMerchant\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Enums\ProductType;
use App\Services\Gmc\GmcOfferId;
use App\Services\Gmc\GmcProductStatusService;
use App\Modules\GoogleMerchant\Jobs\PushProductToGmcJob;
use App\Modules\GoogleMerchant\Jobs\PushVariantToGmcJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Google Merchant Center Management Controller
 */
class GoogleMerchantController extends Controller
{
    public function __construct(
        private GmcOfferId $offerIdService,
        private GmcProductStatusService $statusService
    ) {}

    /**
     * Display GMC products management page
     * Optimized: Only load product list, GMC status will be loaded via AJAX
     */
    public function index(Request $request)
    {
        // Set sidebar active for menu highlighting
        session(['sidebar_active' => 'google-merchant']);
        
        $statusFilter = $request->get('status', 'all'); // all, approved, pending, disapproved, not_synced
        $keyword = $request->get('keyword', '');
        
        // Get products same as /admin/product (only PRODUCT type, not TAXONOMY)
        $query = Product::with(['variants', 'brand'])
            ->where('type', ProductType::PRODUCT->value) // Only products, not taxonomies
            ->where('status', '1')
            ->orderBy('id', 'desc');

        // Filter by keyword if provided
        if ($keyword) {
            $query->where('name', 'like', '%' . $keyword . '%');
        }

        $products = $query->paginate(20);

        // Build list of products/variants for display (without GMC status - will load via AJAX)
        $items = [];
        foreach ($products as $product) {
            // Handle products with variants
            if ($product->has_variants == 1 && $product->variants->count() > 0) {
                foreach ($product->variants as $variant) {
                    $offerId = $this->offerIdService->forVariant($variant);
                    $items[] = [
                        'product' => $product,
                        'variant' => $variant,
                        'offer_id' => $offerId,
                        'gmc_status' => null, // Will be loaded via AJAX
                        'gmc_status_data' => null,
                    ];
                }
            } else {
                // Handle simple products (no variants)
                // Keep offerId consistent with GoogleMerchantService:
                // Prefer first variant offerId (if exists), otherwise fallback to PROD_{product_id}.
                $firstVariant = $product->variants()
                    ->orderBy('position', 'asc')
                    ->orderBy('id', 'asc')
                    ->first();
                $offerId = $firstVariant
                    ? $this->offerIdService->forVariant($firstVariant)
                    : ('PROD_' . (int) $product->id);
                $items[] = [
                    'product' => $product,
                    'variant' => null,
                    'offer_id' => $offerId,
                    'gmc_status' => null, // Will be loaded via AJAX
                    'gmc_status_data' => null,
                ];
            }
        }

        return view('GoogleMerchant::index', [
            'products' => $products,
            'items' => $items,
            'statusFilter' => $statusFilter,
            'keyword' => $keyword,
        ]);
    }

    /**
     * API: Get batch GMC statuses for multiple offer IDs
     * POST /admin/google-merchant/batch-status
     */
    public function batchStatus(Request $request)
    {
        $request->validate([
            'offer_ids' => 'required|array|min:1|max:50', // Limit to 50 per request
            'offer_ids.*' => 'required|string',
        ]);

        try {
            $offerIds = $request->get('offer_ids', []);
            $statuses = $this->statusService->getBatchProductStatuses($offerIds);

            return response()->json([
                'success' => true,
                'data' => $statuses,
            ]);
        } catch (\Throwable $e) {
            Log::error('[GMC] Batch status failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Batch status failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Sync product/variant to GMC
     * POST /admin/google-merchant/sync
     */
    public function sync(Request $request)
    {
        $request->validate([
            'product_id' => 'nullable|integer|exists:posts,id',
            'variant_id' => 'nullable|integer|exists:variants,id',
        ]);

        try {
            if ($request->has('variant_id')) {
                $variant = Variant::findOrFail($request->variant_id);
                PushVariantToGmcJob::dispatch($variant->id);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Variant sync queued successfully',
                    'variant_id' => $variant->id,
                ]);
            } elseif ($request->has('product_id')) {
                $product = Product::findOrFail($request->product_id);
                PushProductToGmcJob::dispatch($product->id);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Product sync queued successfully',
                    'product_id' => $product->id,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Either product_id or variant_id is required',
                ], 400);
            }
        } catch (\Throwable $e) {
            Log::error('[GMC] Sync failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Get GMC status for a product/variant
     * GET /admin/google-merchant/status
     */
    public function getStatus(Request $request)
    {
        $request->validate([
            'product_id' => 'nullable|integer|exists:posts,id',
            'variant_id' => 'nullable|integer|exists:variants,id',
        ]);

        try {
            $offerId = null;
            
            if ($request->has('variant_id')) {
                $variant = Variant::findOrFail($request->variant_id);
                $offerId = $this->offerIdService->forVariant($variant);
            } elseif ($request->has('product_id')) {
                $product = Product::findOrFail($request->product_id);
                $firstVariant = $product->variants()
                    ->orderBy('position', 'asc')
                    ->orderBy('id', 'asc')
                    ->first();
                $offerId = $firstVariant
                    ? $this->offerIdService->forVariant($firstVariant)
                    : ('PROD_' . (int) $product->id);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Either product_id or variant_id is required',
                ], 400);
            }

            $statusData = $this->statusService->getProductStatus($offerId);

            return response()->json([
                'success' => true,
                'data' => [
                    'offer_id' => $offerId,
                    'status' => $statusData,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[GMC] Get status failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Get status failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}

