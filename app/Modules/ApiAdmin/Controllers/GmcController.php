<?php

declare(strict_types=1);

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
     * GET /admin/api/gmc/products/preview?variant_id=123.
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
            if (! $variant || ! $variant->product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Variant not found',
                ], 404);
            }

            $gmcProduct = $this->mapper->map($variant->product, $variant);

            // Convert Google Product object to array for better readability
            $productArray = $this->convertGmcProductToArray($gmcProduct);

            return response()->json([
                'success' => true,
                'data' => [
                    'variant_id' => $variant->id,
                    'product_id' => $variant->product->id,
                    'gmc_payload' => $productArray,
                    'raw_object' => $gmcProduct, // Keep raw object for debugging
                ],
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
     * Body: { "variant_ids": [1,2,3], "dry_run": true|false }.
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
                if (! $variant || ! $variant->product) {
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

    /**
     * Convert Google ShoppingContent Product object to array for JSON preview.
     */
    private function convertGmcProductToArray($gmcProduct): array
    {
        $result = [];

        // Basic fields
        if ($gmcProduct->getOfferId() !== null) {
            $result['offerId'] = $gmcProduct->getOfferId();
        }
        if ($gmcProduct->getTitle() !== null) {
            $result['title'] = $gmcProduct->getTitle();
        }
        if ($gmcProduct->getDescription() !== null) {
            $result['description'] = $gmcProduct->getDescription();
        }
        if ($gmcProduct->getLink() !== null) {
            $result['link'] = $gmcProduct->getLink();
        }
        if ($gmcProduct->getImageLink() !== null) {
            $result['imageLink'] = $gmcProduct->getImageLink();
        }
        if ($gmcProduct->getAdditionalImageLinks() !== null) {
            $result['additionalImageLinks'] = $gmcProduct->getAdditionalImageLinks();
        }
        if ($gmcProduct->getChannel() !== null) {
            $result['channel'] = $gmcProduct->getChannel();
        }
        if ($gmcProduct->getContentLanguage() !== null) {
            $result['contentLanguage'] = $gmcProduct->getContentLanguage();
        }
        if ($gmcProduct->getTargetCountry() !== null) {
            $result['targetCountry'] = $gmcProduct->getTargetCountry();
        }
        if ($gmcProduct->getAvailability() !== null) {
            $result['availability'] = $gmcProduct->getAvailability();
        }
        if ($gmcProduct->getCondition() !== null) {
            $result['condition'] = $gmcProduct->getCondition();
        }
        if ($gmcProduct->getBrand() !== null) {
            $result['brand'] = $gmcProduct->getBrand();
        }
        if ($gmcProduct->getGoogleProductCategory() !== null) {
            $result['googleProductCategory'] = $gmcProduct->getGoogleProductCategory();
        }

        // Price
        $price = $gmcProduct->getPrice();
        if ($price !== null) {
            $result['price'] = [
                'value' => $price->getValue(),
                'currency' => $price->getCurrency(),
            ];
        }

        // Sale Price
        $salePrice = $gmcProduct->getSalePrice();
        if ($salePrice !== null) {
            $result['salePrice'] = [
                'value' => $salePrice->getValue(),
                'currency' => $salePrice->getCurrency(),
            ];
        }
        if ($gmcProduct->getSalePriceEffectiveDate() !== null) {
            $result['salePriceEffectiveDate'] = $gmcProduct->getSalePriceEffectiveDate();
        }

        // Packaging dimensions
        $shippingWeight = $gmcProduct->getShippingWeight();
        if ($shippingWeight !== null) {
            $result['shippingWeight'] = [
                'value' => $shippingWeight->getValue(),
                'unit' => $shippingWeight->getUnit(),
            ];
        }

        $productLength = $gmcProduct->getProductLength();
        if ($productLength !== null) {
            $result['productLength'] = [
                'value' => $productLength->getValue(),
                'unit' => $productLength->getUnit(),
            ];
        }

        $productWidth = $gmcProduct->getProductWidth();
        if ($productWidth !== null) {
            $result['productWidth'] = [
                'value' => $productWidth->getValue(),
                'unit' => $productWidth->getUnit(),
            ];
        }

        $productHeight = $gmcProduct->getProductHeight();
        if ($productHeight !== null) {
            $result['productHeight'] = [
                'value' => $productHeight->getValue(),
                'unit' => $productHeight->getUnit(),
            ];
        }

        return $result;
    }
}
