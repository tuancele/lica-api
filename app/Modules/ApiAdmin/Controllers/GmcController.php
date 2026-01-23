<?php

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Variant;
use App\Services\Gmc\GmcProductMapper;
use App\Services\Gmc\GmcSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GmcController extends Controller
{
    public function __construct(
        private GmcSyncService $syncService,
        private GmcProductMapper $mapper
    ) {}

    /**
     * GET /admin/api/gmc/products/preview?variant_id=123
     */
    public function preview(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'variant_id' => ['required', 'integer', 'exists:variants,id'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $variantId = (int) $request->get('variant_id');
            $variant = Variant::with(['product.brand'])->find($variantId);
            if (!$variant || !$variant->product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Variant not found',
                ], 404);
            }

            $gmcProduct = $this->mapper->map($variant->product, $variant);

            return response()->json([
                'success' => true,
                'data' => $gmcProduct,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('[GMC] Preview failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Preview failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal error',
            ], 500);
        }
    }

    /**
     * POST /admin/api/gmc/products/sync
     * Body: { "variant_ids": [1,2,3], "dry_run": true|false }
     */
    public function sync(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'variant_ids' => ['required', 'array', 'min:1'],
                'variant_ids.*' => ['integer', 'exists:variants,id'],
                'dry_run' => ['nullable', 'boolean'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $dryRun = (bool) ($request->get('dry_run', false));
            $variantIds = array_values(array_unique(array_map('intval', (array) $request->get('variant_ids', []))));

            $results = [];
            foreach ($variantIds as $variantId) {
                $variant = Variant::with(['product.brand'])->find($variantId);
                if (!$variant || !$variant->product) {
                    $results[] = [
                        'variant_id' => $variantId,
                        'success' => false,
                        'message' => 'Variant not found',
                    ];
                    continue;
                }

                try {
                    $sync = $this->syncService->syncVariant($variant, $dryRun);
                    $results[] = [
                        'variant_id' => $variantId,
                        'success' => true,
                        'offer_id' => $sync['offer_id'],
                        'sent' => $sync['sent'],
                    ];
                } catch (\Throwable $e) {
                    $results[] = [
                        'variant_id' => $variantId,
                        'success' => false,
                        'message' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'dry_run' => $dryRun,
                    'results' => $results,
                ],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('[GMC] Sync failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sync failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal error',
            ], 500);
        }
    }
}



