<?php

declare(strict_types=1);

namespace App\Themes\Website\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compare\Models\Compare;
use App\Modules\Deal\Models\Deal;
use App\Modules\Deal\Models\ProductDeal;
use App\Modules\Deal\Models\SaleDeal;
use App\Modules\Dictionary\Models\IngredientBenefit;
use App\Modules\Dictionary\Models\IngredientPaulas;
use App\Modules\Dictionary\Models\IngredientRate;
use App\Modules\FlashSale\Models\ProductSale;
use App\Modules\Post\Models\Post;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Modules\Rate\Models\Rate;
use App\Services\Inventory\Contracts\InventoryServiceInterface;
use App\Services\Warehouse\WarehouseServiceInterface;
use App\Themes\Website\Models\Facebook;
use Session;

class ProductController extends Controller
{
    public function show($slug)
    {
        // Try to find the product
        // IMPORTANT: Chỉ match sản phẩm thật sự, tránh nuốt slug của page/blog/taxonomy
        $post = Product::where([['slug', $slug], ['status', '1'], ['type', 'product']])->first();

        if ($post) {
            $watch = Session::get('product_watched', []);
            if (! in_array($post->id, $watch)) {
                array_push($watch, $post->id);
                Session::put('product_watched', $watch);
            }

            $data['detail'] = $post;
            $data['product_id'] = $post->id;
            $data['gallerys'] = json_decode($post->gallery);
            $variants = Variant::where('product_id', $post->id)->orderBy('position', 'asc')->orderBy('id', 'asc')->get();

            // Get warehouse service and inventory service
            $warehouseService = app(WarehouseServiceInterface::class);
            $inventoryService = app(InventoryServiceInterface::class);
            $now = time();

            // Attach warehouse stock and calculate stock_display with priority: Flash Sale > Deal > Available
            $variants = $variants->map(function ($v) use ($warehouseService, $now) {
                try {
                    // Ensure variant_id is integer (not string) to avoid type mismatch
                    $variantId = (int) $v->id;
                    $variantSku = $v->sku ?? '';

                    // Log for debugging
                    \Log::info('ProductController: Checking Stock', [
                        'variant_id' => $variantId,
                        'variant_sku' => $variantSku,
                        'product_id' => $v->product_id ?? null,
                    ]);

                    // Get base stock data from WarehouseService V2 only - no legacy fallback
                    // WarehouseService will automatically try SKU fallback if variant_id not found
                    $stockData = $warehouseService->getVariantStock($variantId);

                    // Log result of WarehouseService query
                    \Log::info('ProductController: WarehouseService result', [
                        'variant_id' => $variantId,
                        'variant_sku' => $variantSku,
                        'physical_stock' => $stockData['physical_stock'] ?? 0,
                        'available_stock' => $stockData['available_stock'] ?? 0,
                        'flash_sale_stock' => $stockData['flash_sale_stock'] ?? 0,
                        'deal_stock' => $stockData['deal_stock'] ?? 0,
                    ]);

                    // If still 0, log all product_warehouse entries for this product to debug
                    if (($stockData['physical_stock'] ?? 0) == 0 && isset($v->product_id)) {
                        $allWarehouseRows = \App\Modules\Warehouse\Models\ProductWarehouse::query()
                            ->join('variants as v', 'v.id', '=', 'product_warehouse.variant_id')
                            ->where('v.product_id', $v->product_id)
                            ->select('product_warehouse.*', 'v.id as variant_id_from_join', 'v.sku as variant_sku_from_join')
                            ->get()
                            ->map(function ($row) {
                                return [
                                    'product_warehouse_id' => $row->id,
                                    'warehouse_id' => $row->warehouse_id,
                                    'variant_id' => $row->variant_id,
                                    'variant_id_from_join' => $row->variant_id_from_join,
                                    'variant_sku_from_join' => $row->variant_sku_from_join,
                                    'physical_stock' => $row->physical_stock,
                                    'qty' => $row->qty,
                                    'type' => $row->type,
                                ];
                            })
                            ->toArray();

                        \Log::info('ProductController: All product_warehouse rows for product_id', [
                            'product_id' => $v->product_id,
                            'variant_id_requested' => $variantId,
                            'variant_sku_requested' => $variantSku,
                            'all_warehouse_rows' => $allWarehouseRows,
                        ]);
                    }

                    // Warehouse V2: Get stock values directly from WarehouseService (from inventory_stocks)
                    // Physical is the base value for calculation, never displayed
                    $v->physical_stock = (int) ($stockData['physical_stock'] ?? 0);

                    // Get stock values from WarehouseService (already calculated from inventory_stocks)
                    $flashSaleStock = (int) ($stockData['flash_sale_stock'] ?? 0);  // From inventory_stocks.flash_sale_hold
                    $dealStock = (int) ($stockData['deal_stock'] ?? 0);  // From inventory_stocks.deal_hold
                    $availableStock = (int) ($stockData['available_stock'] ?? 0);  // From inventory_stocks.available_stock

                    // Check if Flash Sale is active (to determine if we should use flash_sale_stock)
                    $hasActiveFlashSale = ProductSale::query()
                        ->join('flashsales as fs', 'fs.id', '=', 'productsales.flashsale_id')
                        ->where('productsales.variant_id', $v->id)
                        ->where('fs.status', '1')
                        ->where('fs.start', '<=', $now)
                        ->where('fs.end', '>=', $now)
                        ->exists();

                    // Check if Deal is active (to determine if we should use deal_stock)
                    $hasActiveDeal = SaleDeal::query()
                        ->join('deals as d', 'd.id', '=', 'deal_sales.deal_id')
                        ->where('d.status', '1')
                        ->where('d.start', '<=', $now)
                        ->where('d.end', '>=', $now)
                        ->where('deal_sales.status', '1')
                        ->where(function ($q) use ($v) {
                            $q->where(function ($q2) use ($v) {
                                $q2->whereNotNull('deal_sales.variant_id')
                                    ->where('deal_sales.variant_id', $v->id);
                            })->orWhere(function ($q3) use ($v) {
                                $q3->whereNull('deal_sales.variant_id')
                                    ->where('deal_sales.product_id', $v->product_id);
                            });
                        })
                        ->exists();

                    // Store values for reference
                    $v->warehouse_stock = $availableStock;
                    $v->flash_sale_stock = $flashSaleStock;
                    $v->deal_stock = $dealStock;
                    $v->fs_qty = $flashSaleStock;
                    $v->deal_qty = $dealStock;

                    // Apply priority logic for stock_display:
                    // Priority 1: Flash Sale Stock (if FS active AND flash_sale_stock > 0)
                    // Priority 2: Deal Stock (if no FS or FS = 0, AND Deal active AND deal_stock > 0)
                    // Priority 3: Available Stock (if no FS/Deal or both = 0)
                    // End: Out of Stock (if all = 0)
                    if ($hasActiveFlashSale && $flashSaleStock > 0) {
                        // Priority 1: Flash Sale Stock
                        $v->stock_display = $flashSaleStock;
                        $v->stock_source = 'flash_sale';
                        $v->has_flash_sale = true;
                        $v->has_deal = false;
                    } elseif ((! $hasActiveFlashSale || $flashSaleStock == 0) && $hasActiveDeal && $dealStock > 0) {
                        // Priority 2: Deal Stock (only if no FS or FS = 0, AND Deal active AND deal_stock > 0)
                        $v->stock_display = $dealStock;
                        $v->stock_source = 'deal';
                        $v->has_flash_sale = false;
                        $v->has_deal = true;
                    } elseif ($availableStock > 0) {
                        // Priority 3: Available Stock
                        $v->stock_display = $availableStock;
                        $v->stock_source = 'warehouse';
                        $v->has_flash_sale = false;
                        $v->has_deal = false;
                    } else {
                        // End: Out of Stock (all = 0)
                        $v->stock_display = 0;
                        $v->stock_source = 'warehouse';
                        $v->has_flash_sale = false;
                        $v->has_deal = false;
                    }

                    // Log stock_display for debugging
                    \Log::info('ProductController: Variant stock calculated with priority logic', [
                        'variant_id' => $variantId,
                        'variant_sku' => $variantSku,
                        'physical_stock' => $v->physical_stock,
                        'flash_sale_stock' => $flashSaleStock,
                        'deal_stock' => $dealStock,
                        'available_stock' => $availableStock,
                        'has_active_flash_sale' => $hasActiveFlashSale,
                        'has_active_deal' => $hasActiveDeal,
                        'stock_display' => $v->stock_display,
                        'stock_source' => $v->stock_source,
                    ]);
                } catch (\Throwable $e) {
                    // Warehouse V2 only - if error, return 0 (out of stock)
                    $v->warehouse_stock = 0;
                    $v->physical_stock = 0;
                    $v->flash_sale_stock = 0;
                    $v->deal_stock = 0;
                    $v->stock_display = 0;
                    $v->stock_source = 'warehouse';
                    $v->has_flash_sale = false;
                    $v->has_deal = false;
                    $v->fs_qty = 0;
                    $v->deal_qty = 0;

                    \Log::error('ProductController: WarehouseService error, stock set to 0', [
                        'variant_id' => $v->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                return $v;
            });
            $first = $variants->first();
            // Nếu không có variant thì tạo object rỗng để tránh lỗi view
            if (! $first) {
                // Thử tìm default variant của product (nếu tồn tại)
                $defaultVariant = $post->variant($post->id);
                if ($defaultVariant) {
                    try {
                        $stockData = $warehouseService->getVariantStock($defaultVariant->id);
                        $defaultVariant->warehouse_stock = (int) ($stockData['available_stock'] ?? $stockData['current_stock'] ?? 0);
                        $defaultVariant->physical_stock = (int) ($stockData['physical_stock'] ?? 0);
                        $defaultVariant->flash_sale_stock = (int) ($stockData['flash_sale_stock'] ?? 0);
                        $defaultVariant->deal_stock = (int) ($stockData['deal_stock'] ?? 0);

                        // Calculate stock_display (same logic as above)
                        $flashSaleRemaining = max(0, (int) (ProductSale::query()
                            ->join('flashsales as fs', 'fs.id', '=', 'productsales.flashsale_id')
                            ->where('productsales.variant_id', $defaultVariant->id)
                            ->where('fs.status', '1')
                            ->where('fs.start', '<=', $now)
                            ->where('fs.end', '>=', $now)
                            ->selectRaw('SUM(productsales.number - productsales.buy) as remaining')
                            ->value('remaining') ?? 0));

                        $dealRemaining = max(0, (int) (SaleDeal::query()
                            ->join('deals as d', 'd.id', '=', 'deal_sales.deal_id')
                            ->where('d.status', '1')
                            ->where('d.start', '<=', $now)
                            ->where('d.end', '>=', $now)
                            ->where('deal_sales.status', '1')
                            ->where(function ($q) use ($defaultVariant) {
                                $q->where(function ($q2) use ($defaultVariant) {
                                    $q2->whereNotNull('deal_sales.variant_id')
                                        ->where('deal_sales.variant_id', $defaultVariant->id);
                                })->orWhere(function ($q3) use ($defaultVariant) {
                                    $q3->whereNull('deal_sales.variant_id')
                                        ->where('deal_sales.product_id', $defaultVariant->product_id);
                                });
                            })
                            ->selectRaw('SUM(deal_sales.qty - COALESCE(deal_sales.buy, 0)) as remaining')
                            ->value('remaining') ?? 0));

                        if ($flashSaleRemaining > 0) {
                            $defaultVariant->stock_display = $flashSaleRemaining;
                            $defaultVariant->stock_source = 'flash_sale';
                            $defaultVariant->has_flash_sale = true;
                            $defaultVariant->has_deal = false;
                        } elseif ($dealRemaining > 0) {
                            $defaultVariant->stock_display = $dealRemaining;
                            $defaultVariant->stock_source = 'deal';
                            $defaultVariant->has_flash_sale = false;
                            $defaultVariant->has_deal = true;
                        } else {
                            $defaultVariant->stock_display = $defaultVariant->warehouse_stock;
                            $defaultVariant->stock_source = 'warehouse';
                            $defaultVariant->has_flash_sale = false;
                            $defaultVariant->has_deal = false;
                        }
                        $defaultVariant->fs_qty = $flashSaleRemaining;
                        $defaultVariant->deal_qty = $dealRemaining;
                    } catch (\Throwable $e) {
                        // Warehouse V2 only - if error, return 0 (out of stock)
                        $defaultVariant->warehouse_stock = 0;
                        $defaultVariant->physical_stock = 0;
                        $defaultVariant->stock_display = 0;
                        $defaultVariant->stock_source = 'warehouse';
                        $defaultVariant->has_flash_sale = false;
                        $defaultVariant->has_deal = false;
                        $defaultVariant->fs_qty = 0;
                        $defaultVariant->deal_qty = 0;

                        \Log::error('ProductController: WarehouseService error for defaultVariant, stock set to 0', [
                            'variant_id' => $defaultVariant->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                    $first = $defaultVariant;
                    $variants = collect([$defaultVariant]);
                } else {
                    // Warehouse V2 only - no legacy stock
                    $first = new Variant;
                    $first->price = 0;
                    $first->sku = '';
                    $first->warehouse_stock = 0;
                    $first->physical_stock = 0;
                    $first->stock_display = 0;
                    $first->stock_source = 'warehouse';
                    $first->has_flash_sale = false;
                    $first->has_deal = false;
                    $first->fs_qty = 0;
                    $first->deal_qty = 0;
                    $variants = collect([$first]);
                }
            } else {
                // Ensure first variant has all stock properties set
                if (! isset($first->stock_display)) {
                    $first->stock_display = $first->warehouse_stock ?? 0;
                    $first->stock_source = $first->stock_source ?? 'warehouse';
                    $first->has_flash_sale = $first->has_flash_sale ?? false;
                    $first->has_deal = $first->has_deal ?? false;
                    $first->fs_qty = $first->fs_qty ?? 0;
                    $first->deal_qty = $first->deal_qty ?? 0;
                }
            }
            $data['variants'] = $variants;
            $data['first'] = $first;

            // Calculate if product is out of stock based on Warehouse
            // Use stock_display from first variant (already calculated with priority: Flash Sale > Deal > Available)
            $data['isOutOfStock'] = ($first->stock_display ?? 0) <= 0;

            // Log final stock_display before returning view
            \Log::info('ProductController: Final stock_display before view', [
                'product_id' => $post->id,
                'first_variant_id' => $first->id ?? null,
                'first_stock_display' => $first->stock_display ?? null,
                'first_stock_source' => $first->stock_source ?? null,
            ]);

            // Special log for SKU LC60VN (clean test)
            if (isset($first->sku) && $first->sku === 'LC60VN') {
                \Log::info('CLEAN TEST - SKU: LC60VN - Final Stock: '.($first->stock_display ?? 0), [
                    'variant_id' => $first->id ?? null,
                    'stock_display' => $first->stock_display ?? 0,
                    'physical_stock' => $first->physical_stock ?? 0,
                    'warehouse_stock' => $first->warehouse_stock ?? 0,
                    'flash_sale_stock' => $first->flash_sale_stock ?? 0,
                    'deal_stock' => $first->deal_stock ?? 0,
                    'stock_source' => $first->stock_source ?? null,
                ]);
            }

            // Get all categories from cat_id array (not just first one)
            $arrCate = json_decode($post->cat_id, true);
            $catIds = (is_array($arrCate) && ! empty($arrCate)) ? $arrCate : [];

            // Load all categories
            $categories = [];
            if (! empty($catIds)) {
                $categories = Post::select('id', 'name', 'slug', 'cat_id')
                    ->where('type', 'taxonomy')
                    ->whereIn('id', $catIds)
                    ->orderByRaw('FIELD(id, '.implode(',', $catIds).')')
                    ->get();
            }

            // Get first category for backward compatibility
            $catid = ! empty($catIds) ? $catIds[0] : ($post->cat_id ?? '');
            $data['category'] = ! empty($categories) ? $categories->first() : Post::select('id', 'name', 'slug', 'cat_id')->where([['type', 'taxonomy'], ['id', $catid]])->first();
            $data['categories'] = $categories; // All categories for breadcrumb

            // Attach category to product model for blade template
            if ($data['category']) {
                $post->category = $data['category'];
            }

            $data['rates'] = Rate::where([['status', '1'], ['product_id', $post->id]])->orderBy('created_at', 'desc')->limit(5)->get();

            // Get related products and attach warehouse stock info
            $relatedProducts = Product::join('variants', 'variants.product_id', '=', 'posts.id')
                ->select('posts.id', 'posts.name', 'posts.slug', 'posts.image', 'posts.brand_id', 'variants.price as price', 'variants.size_id as size_id', 'variants.color_id as color_id', 'variants.id as variant_id')
                ->where([['status', '1'], ['type', 'product'], ['posts.id', '!=', $post->id]])
                ->where('cat_id', 'like', '%"'.$catid.'"%')
                ->groupBy('product_id')
                ->limit(9)
                ->orderBy('posts.created_at', 'desc')
                ->get();

            // Attach warehouse stock to related products
            $warehouseService = app(WarehouseServiceInterface::class);
            $relatedProducts = $relatedProducts->map(function ($product) use ($warehouseService) {
                try {
                    $variantId = $product->variant_id ?? null;
                    if ($variantId) {
                        $stockInfo = $warehouseService->getVariantStock($variantId);
                        $product->warehouse_stock = (int) ($stockInfo['available_stock'] ?? 0);
                        $product->stock_display = (int) ($stockInfo['available_stock'] ?? 0); // Use available_stock for related products
                    } else {
                        $product->warehouse_stock = 0;
                        $product->stock_display = 0;
                    }
                } catch (\Throwable $e) {
                    $product->warehouse_stock = 0;
                    $product->stock_display = 0;
                }

                return $product;
            });

            $data['products'] = $relatedProducts;

            // Legacy color/size selector (only for old variant mode)
            $data['colors'] = Variant::select('color_id')->where('product_id', $post->id)->distinct()->get();

            if (Session::has('product_watched')) {
                $data['watchs'] = Product::join('variants', 'variants.product_id', '=', 'posts.id')
                    ->select('posts.id', 'posts.name', 'posts.slug', 'posts.image', 'posts.brand_id', 'posts.stock', 'variants.price as price', 'variants.size_id as size_id', 'variants.color_id as color_id')
                    ->where([['status', '1'], ['type', 'product']])
                    ->whereIn('posts.id', Session::get('product_watched'))
                    ->groupBy('product_id')
                    ->orderBy('posts.created_at', 'desc')->get();
            }

            $data['t_rates'] = Rate::select('id', 'rate')->where([['status', '1'], ['product_id', $post->id]])->get();

            // Tracking
            $dataf = [
                'product_id' => $post->id,
                'price' => $first->price ?? 0,
                'url' => $post->slug,
                'event' => 'ViewContent',
            ];
            Facebook::track($dataf);

            $data['compares'] = Compare::where([['status', '1'], ['brand', strtolower($post->brand->name ?? '')], ['name', 'like', $post->name.'%']])->groupby('store_id')->distinct()->limit(5)->get();

            // Deal sốc
            $now = strtotime(date('Y-m-d H:i:s'));
            $deal_id = ProductDeal::where('product_id', $post->id)->where('status', 1)->pluck('deal_id')->toArray();
            $deal = Deal::whereIn('id', $deal_id)->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]])->first();
            if ($deal) {
                $data['deal'] = $deal;
                $saledeals = SaleDeal::with(['product', 'variant'])->where([['deal_id', $deal->id], ['status', '1']])->get();

                // Gắn trạng thái tồn kho/quỹ cho từng sản phẩm mua kèm
                $warehouse = app(WarehouseServiceInterface::class);
                $saledeals = $saledeals->map(function ($sale) use ($warehouse) {
                    $remaining = max(0, ((int) $sale->qty) - ((int) ($sale->buy ?? 0)));
                    $stock = 0;
                    try {
                        if ($sale->variant_id) {
                            $stockInfo = $warehouse->getVariantStock($sale->variant_id);
                            $stock = (int) ($stockInfo['current_stock'] ?? 0);
                        } elseif ($sale->product) {
                            $stock = (int) ($sale->product->stock ?? 0);
                        }
                    } catch (\Throwable $e) {
                        // Nếu lỗi kho, coi như hết để an toàn
                        $stock = 0;
                    }

                    $sale->remaining_quota = $remaining;
                    $sale->physical_stock = $stock;
                    $sale->available = $remaining > 0 && $stock > 0;

                    return $sale;
                });

                $data['saledeals'] = $saledeals;
            }

            // Process ingredient string for view
            $rawIngredient = (string) ($post->ingredient ?? '');
            $rawIngredient = trim(strip_tags($rawIngredient));
            $tokens = [];
            if ($rawIngredient !== '') {
                $parts = explode(',', $rawIngredient);
                foreach ($parts as $p) {
                    $t = trim($p);
                    if ($t === '') {
                        continue;
                    }
                    $tokens[] = $t;
                }
            }
            $tokens = array_values(array_unique($tokens));

            $matchedIngredients = [];
            $benefitIds = [];
            $rateIds = [];

            foreach ($tokens as $t) {
                // Try exact match first (case-insensitive)
                $row = IngredientPaulas::query()
                    ->select(['id', 'name', 'slug', 'benefit_id', 'rate_id'])
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($t, 'UTF-8')])
                    ->first();

                if (! $row) {
                    // Fallback: try best-effort match by trimmed name
                    $row = IngredientPaulas::query()
                        ->select(['id', 'name', 'slug', 'benefit_id', 'rate_id'])
                        ->where('name', $t)
                        ->first();
                }

                if (! $row) {
                    continue;
                }

                $matchedIngredients[] = $row;

                $ids = $row->benefit_id ?? [];
                if (is_string($ids)) {
                    $decoded = json_decode($ids, true);
                    $ids = is_array($decoded) ? $decoded : [];
                }
                if (is_array($ids)) {
                    foreach ($ids as $id) {
                        $bid = (int) $id;
                        if ($bid > 0) {
                            $benefitIds[$bid] = true;
                        }
                    }
                }

                $rid = (int) ($row->rate_id ?? 0);
                if ($rid > 0) {
                    $rateIds[$rid] = true;
                }
            }

            $benefitMap = IngredientBenefit::query()
                ->whereIn('id', array_keys($benefitIds))
                ->get()
                ->keyBy('id');

            $rateMap = IngredientRate::query()
                ->whereIn('id', array_keys($rateIds))
                ->get()
                ->keyBy('id');

            $processedIngredients = [];
            foreach ($matchedIngredients as $row) {
                $ids = $row->benefit_id ?? [];
                if (is_string($ids)) {
                    $decoded = json_decode($ids, true);
                    $ids = is_array($decoded) ? $decoded : [];
                }
                if (! is_array($ids)) {
                    $ids = [];
                }

                $benefits = [];
                foreach ($ids as $id) {
                    $bid = (int) $id;
                    if ($bid <= 0) {
                        continue;
                    }
                    $b = $benefitMap->get($bid);
                    if ($b) {
                        // If table has icon column, it will be included automatically by Eloquent
                        $benefits[] = [
                            'name' => (string) ($b->name ?? ''),
                            'icon' => (string) ($b->icon ?? ''),
                        ];
                    }
                }

                $rates = [];
                $rid = (int) ($row->rate_id ?? 0);
                if ($rid > 0) {
                    $r = $rateMap->get($rid);
                    if ($r) {
                        $rates[] = [
                            'name' => (string) ($r->name ?? ''),
                            'icon' => (string) ($r->icon ?? ''),
                        ];
                    }
                }

                $processedIngredients[] = [
                    'name' => (string) ($row->name ?? ''),
                    'slug' => (string) ($row->slug ?? ''),
                    'benefit_icons' => $benefits,
                    'skin_types' => $rates,
                ];
            }

            $data['processedIngredients'] = $processedIngredients;

            return view('Website::product.detail', $data);
        } else {
            // Nếu không phải sản phẩm, fallback về HomeController@post để giữ nguyên behavior cũ
            return app()->call(\App\Themes\Website\Controllers\HomeController::class.'@post', ['url' => $slug]);
        }
    }
}
