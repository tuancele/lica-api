<?php

declare(strict_types=1);

namespace App\Services\Product;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Exceptions\ProductCreationException;
use App\Exceptions\ProductDeletionException;
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\ProductUpdateException;
use App\Modules\Dictionary\Models\IngredientPaulas;
use App\Modules\Order\Models\OrderDetail;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Modules\Redirection\Models\Redirection;
use App\Repositories\Product\ProductRepositoryInterface;
use App\Services\Image\ImageServiceInterface;
use App\Services\Warehouse\WarehouseServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service class for Product business logic.
 *
 * This service handles all product-related business operations,
 * separating business logic from controllers and data access.
 */
class ProductService implements ProductServiceInterface
{
    public function __construct(
        private ProductRepositoryInterface $repository,
        private ImageServiceInterface $imageService,
        private WarehouseServiceInterface $warehouseService
    ) {}

    /**
     * Create a new product.
     *
     * @param  array  $data  Product data
     *
     * @throws \Exception
     */
    public function createProduct(array $data): Product
    {
        DB::beginTransaction();

        try {
            // Process gallery images (create: không có existing gallery)
            $gallery = $this->imageService->processGallery(
                $data['imageOther'] ?? [],
                $data['r2_session_key'] ?? null,
                []
            );

            // Get main image from gallery
            $image = $this->imageService->getMainImage($gallery);

            // Process ingredients
            $ingredient = $this->processIngredients($data['ingredient'] ?? '');

            // Prepare product data
            $productData = [
                'name' => $data['name'],
                'slug' => $data['slug'],
                'content' => $data['content'] ?? '',
                'description' => $data['description'] ?? '',
                'image' => $image,
                'gallery' => json_encode($gallery),
                'video' => $data['video'] ?? null,
                'cat_id' => json_encode($data['cat_id'] ?? []),
                'brand_id' => $data['brand_id'] ?? null,
                'origin_id' => $data['origin_id'] ?? null,
                'status' => $data['status'] ?? ProductStatus::ACTIVE->value,
                'type' => ProductType::PRODUCT->value,
                'has_variants' => (int) ($data['has_variants'] ?? 0),
                'option1_name' => ($data['has_variants'] ?? 0) ? ($data['option1_name'] ?? null) : null,
                'feature' => $data['feature'] ?? '0',
                'best' => $data['best'] ?? '0',
                'stock' => $data['stock'] ?? '1',
                'ingredient' => $ingredient,
                'verified' => $data['verified'] ?? '0',
                'seo_title' => $data['seo_title'] ?? null,
                'seo_description' => $data['seo_description'] ?? null,
                'cbmp' => $data['cbmp'] ?? null,
                'user_id' => $data['user_id'] ?? auth()->id(),
                // Packaging dimensions (grams, cm)
                'weight' => $data['weight'] ?? 0,
                'length' => $data['length'] ?? 0,
                'width' => $data['width'] ?? 0,
                'height' => $data['height'] ?? 0,
            ];

            // Create product
            $product = $this->repository->create($productData);

            $hasVariants = (int) ($data['has_variants'] ?? 0) === 1;
            $createdVariants = [];

            if ($hasVariants) {
                $createdVariants = $this->syncVariantsFromJson($product->id, $data['variants_json'] ?? '', $image);
            } else {
                // Create default variant (single product)
                $variant = $this->createDefaultVariant($product->id, [
                    'sku' => $data['sku'] ?? 'SKU-'.time().'-'.rand(10, 99),
                    'image' => $image,
                    'price' => $this->parsePrice($data['price'] ?? 0),
                    'weight' => $data['weight'] ?? 0,
                    'stock' => (int) ($data['stock_qty'] ?? 0),
                ]);
                if ($variant) {
                    // Reload variant to ensure we have fresh data
                    $variant->refresh();
                    $createdVariants[] = $variant;
                }
            }

            DB::commit();

            // Reload product to ensure fresh data
            $product->refresh();

            // Auto create import receipt if initial stock > 0
            // Load all variants for the product to ensure we have complete data
            // Use fresh() to ensure we get the latest data from database
            $allVariants = Variant::where('product_id', $product->id)->get();

            Log::info('Loaded variants for import receipt creation', [
                'product_id' => $product->id,
                'variants_count' => $allVariants->count(),
                'variants' => $allVariants->map(function ($v) {
                    return [
                        'id' => $v->id,
                        'sku' => $v->sku,
                        'stock' => $v->stock,
                        'price' => $v->price,
                    ];
                })->toArray(),
            ]);

            $this->createInitialStockImportReceipt($product, $allVariants->all());

            // Clear cache (selective clearing instead of flush)
            Cache::forget("product:{$product->id}");
            // Only use tags if cache driver supports it
            try {
                Cache::tags(['products:list'])->flush();
            } catch (\Exception $e) {
                // Cache driver doesn't support tags, use regular flush for list cache
                Cache::forget('products:list:*');
            }

            // Clear session URLs
            $this->imageService->clearSessionUrls($data['r2_session_key'] ?? null);

            Log::info('Product created successfully', [
                'product_id' => $product->id,
                'user_id' => auth()->id(),
            ]);

            return $product;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Product creation failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw new ProductCreationException($e->getMessage());
        }
    }

    /**
     * Update an existing product.
     *
     * @param  int  $id  Product ID
     * @param  array  $data  Updated product data
     *
     * @throws ProductNotFoundException|ProductUpdateException
     */
    public function updateProduct(int $id, array $data): Product
    {
        $product = $this->repository->find($id);

        if (! $product) {
            throw new ProductNotFoundException("Product with ID {$id} not found");
        }

        DB::beginTransaction();

        try {
            // Process gallery images
            // Truyền thêm gallery hiện tại từ DB để tránh mất ảnh cũ nếu vì lý do nào đó
            // form không gửi đủ imageOther[]. Session chỉ chứa ảnh mới upload.
            $existingGallery = [];
            if (! empty($product->gallery)) {
                $decoded = json_decode($product->gallery, true);
                $existingGallery = is_array($decoded) ? $decoded : [];
            }

            // Gallery sync rule:
            // - Nếu form gửi imageOther[]: ưu tiên THỨ TỰ trong form.
            // - Nhưng cần chống mất ảnh cũ do DOM/serialize: merge missing existing images nếu không nằm trong danh sách removed.
            // - Xóa ảnh phải explicit qua imageOtherRemoved[] để không bị restore.
            // - Nếu form không gửi imageOther[] (user không đụng vào gallery), dùng gallery hiện tại từ DB.
            $formImages = $data['imageOther'] ?? [];
            if (! is_array($formImages)) {
                $formImages = [];
            }
            // Loại bỏ giá trị rỗng / null trước khi xử lý
            $formImages = array_values(array_filter($formImages, function ($v) {
                return is_string($v) && trim($v) !== '';
            }));

            $removed = $data['imageOtherRemoved'] ?? [];
            if (! is_array($removed)) {
                $removed = [];
            }
            $removed = array_values(array_filter($removed, function ($v) {
                return is_string($v) && trim($v) !== '';
            }));

            // Remove deleted from existing and form list
            if (! empty($removed)) {
                $existingGallery = array_values(array_diff($existingGallery, $removed));
                $formImages = array_values(array_diff($formImages, $removed));
            }

            $useExisting = empty($formImages);

            if (! $useExisting) {
                // If DOM/serialize missed some existing images, append them back (but not those removed)
                foreach ($existingGallery as $oldUrl) {
                    if (! in_array($oldUrl, $formImages, true)) {
                        $formImages[] = $oldUrl;
                    }
                }
            }

            $gallery = $this->imageService->processGallery(
                $formImages,
                $data['r2_session_key'] ?? null,
                $useExisting ? $existingGallery : []
            );

            // Get main image from gallery
            $image = $this->imageService->getMainImage($gallery) ?? $product->image;

            // Process ingredients
            $ingredient = isset($data['ingredient'])
                ? $this->processIngredients($data['ingredient'])
                : $product->ingredient;

            // Store old slug for redirection
            $oldSlug = $product->slug;

            // Prepare update data
            $updateData = [
                'name' => $data['name'],
                'slug' => $data['slug'],
                'content' => $data['content'] ?? $product->content,
                'description' => $data['description'] ?? $product->description,
                'image' => $image,
                'gallery' => json_encode($gallery),
                'video' => $data['video'] ?? $product->video,
                'cat_id' => json_encode($data['cat_id'] ?? json_decode($product->cat_id ?? '[]', true)),
                'brand_id' => $data['brand_id'] ?? $product->brand_id,
                'origin_id' => $data['origin_id'] ?? $product->origin_id,
                'status' => $data['status'] ?? $product->status,
                'has_variants' => (int) ($data['has_variants'] ?? ($product->has_variants ?? 0)),
                'option1_name' => ((int) ($data['has_variants'] ?? ($product->has_variants ?? 0)) === 1) ? ($data['option1_name'] ?? $product->option1_name) : null,
                'feature' => $data['feature'] ?? $product->feature,
                'best' => $data['best'] ?? $product->best,
                'stock' => $data['stock'] ?? $product->stock,
                'ingredient' => $ingredient,
                'seo_title' => $data['seo_title'] ?? $product->seo_title,
                'seo_description' => $data['seo_description'] ?? $product->seo_description,
                'cbmp' => $data['cbmp'] ?? $product->cbmp,
                'user_id' => auth()->id(),
                // Packaging dimensions (grams, cm)
                'weight' => $data['weight'] ?? $product->weight,
                'length' => $data['length'] ?? $product->length,
                'width' => $data['width'] ?? $product->width,
                'height' => $data['height'] ?? $product->height,
            ];

            // Update product
            $this->repository->update($id, $updateData);

            // Sync variants
            $hasVariants = (int) ($data['has_variants'] ?? 0) === 1;
            if ($hasVariants) {
                $this->syncVariantsFromJson($id, $data['variants_json'] ?? '', $image);
            } else {
                $this->syncSingleVariant($id, [
                    'sku' => $data['sku'] ?? null,
                    'price' => $this->parsePrice($data['price'] ?? 0),
                    'weight' => $data['weight'] ?? 0,
                    'stock' => (int) ($data['stock_qty'] ?? 0),
                    'image' => $image,
                ]);
            }

            // Handle slug change (create redirection)
            if ($oldSlug !== $data['slug']) {
                $this->handleSlugChange($oldSlug, $data['slug']);
            }

            DB::commit();

            // Clear cache (selective clearing instead of flush)
            Cache::forget("product:{$id}");
            Cache::forget("product:{$id}:relations");
            // Only use tags if cache driver supports it
            try {
                Cache::tags(['products:list'])->flush();
            } catch (\Exception $e) {
                // Cache driver doesn't support tags, skip
            }

            // Clear session URLs
            $this->imageService->clearSessionUrls($data['r2_session_key'] ?? null);

            Log::info('Product updated successfully', [
                'product_id' => $id,
                'user_id' => auth()->id(),
            ]);

            return $this->repository->find($id);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Product update failed', [
                'product_id' => $id,
                'error' => $e->getMessage(),
            ]);

            throw new ProductUpdateException($e->getMessage());
        }
    }

    /**
     * Delete a product.
     *
     * @param  int  $id  Product ID
     *
     * @throws ProductNotFoundException|ProductDeletionException
     */
    public function deleteProduct(int $id): bool
    {
        $product = $this->repository->find($id);

        if (! $product) {
            throw new ProductNotFoundException("Product with ID {$id} not found");
        }

        // Check if product has orders
        if ($this->hasOrders($id)) {
            throw new ProductDeletionException('Không thể xóa sản phẩm đã có đơn hàng');
        }

        DB::beginTransaction();

        try {
            // Delete variants first
            Variant::where('product_id', $id)->delete();

            // Delete product
            $this->repository->delete($id);

            DB::commit();

            // Clear cache (selective clearing instead of flush)
            Cache::forget("product:{$id}");
            Cache::forget("product:{$id}:relations");
            // Only use tags if cache driver supports it
            try {
                Cache::tags(['products:list'])->flush();
            } catch (\Exception $e) {
                // Cache driver doesn't support tags, skip
            }

            Log::info('Product deleted successfully', [
                'product_id' => $id,
                'user_id' => auth()->id(),
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Product deletion failed', [
                'product_id' => $id,
                'error' => $e->getMessage(),
            ]);

            throw new ProductDeletionException($e->getMessage());
        }
    }

    /**
     * Get product with all relations.
     *
     * @param  int  $id  Product ID
     *
     * @throws ProductNotFoundException
     */
    public function getProductWithRelations(int $id): Product
    {
        $product = $this->repository->findWithRelations($id);

        if (! $product) {
            throw new ProductNotFoundException("Product with ID {$id} not found");
        }

        return $product;
    }

    /**
     * Get paginated products with filters.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getProducts(array $filters = [], int $perPage = 10)
    {
        return $this->repository->paginate($filters, $perPage);
    }

    /**
     * Create default variant for product.
     */
    private function createDefaultVariant(int $productId, array $data): ?Variant
    {
        $stock = (int) ($data['stock'] ?? 0);

        Log::info('Creating default variant', [
            'product_id' => $productId,
            'sku' => $data['sku'] ?? 'N/A',
            'stock' => $stock,
            'price' => $data['price'] ?? 0,
        ]);

        $variant = Variant::create([
            'sku' => $data['sku'],
            'product_id' => $productId,
            'option1_value' => null,
            'image' => $data['image'],
            'size_id' => 0,
            'color_id' => 0,
            'weight' => $data['weight'] ?? 0,
            'price' => $data['price'],
            'stock' => $stock,
            'position' => 0,
            'user_id' => auth()->id(),
        ]);

        // Reload to ensure we have the saved data
        $variant->refresh();

        Log::info('Default variant created', [
            'variant_id' => $variant->id,
            'stock_saved' => $variant->stock,
            'stock_expected' => $stock,
        ]);

        return $variant;
    }

    /**
     * Sync variants from variants_json (Shopee style 1-level).
     *
     * @return array Array of created/updated Variant models
     */
    private function syncVariantsFromJson(int $productId, string $variantsJson, ?string $fallbackImage = null): array
    {
        $payload = json_decode($variantsJson, true);
        if (! is_array($payload) || ! isset($payload['variants']) || ! is_array($payload['variants'])) {
            throw new \InvalidArgumentException('Dữ liệu phân loại không hợp lệ (variants_json).');
        }

        $variants = $payload['variants'];
        if (count($variants) === 0) {
            throw new \InvalidArgumentException('Bạn cần tạo ít nhất 1 phân loại.');
        }

        // Get product slug for auto-generating SKU if needed
        $product = Product::find($productId);
        $productSlug = $product ? $product->slug : 'product-'.$productId;

        // Auto-generate SKU for empty ones and validate uniqueness in payload
        $seenSku = [];
        foreach ($variants as $pos => &$v) {
            $sku = trim((string) ($v['sku'] ?? ''));
            if ($sku === '') {
                // Auto-generate SKU: {product-slug}-{option1_value}-{position}
                $optionValue = trim((string) ($v['option1_value'] ?? ''));
                $safeOption = preg_replace('/[^a-z0-9]+/i', '-', strtolower($optionValue));
                $safeOption = trim($safeOption, '-');
                if ($safeOption === '') {
                    $safeOption = 'variant';
                }
                $sku = $productSlug.'-'.$safeOption.'-'.($pos + 1);
                $v['sku'] = $sku; // Update in array for later use
            }
            if (in_array($sku, $seenSku, true)) {
                throw new \InvalidArgumentException('SKU bị trùng trong danh sách phân loại: '.$sku);
            }
            $seenSku[] = $sku;
        }
        unset($v); // Break reference

        $existing = Variant::where('product_id', $productId)->get()->keyBy('id');
        $keepIds = [];
        $createdVariants = [];

        foreach ($variants as $pos => $v) {
            $variantId = isset($v['id']) && $v['id'] !== '' ? (int) $v['id'] : null;
            $optionValue = trim((string) ($v['option1_value'] ?? ''));
            $sku = trim((string) ($v['sku'] ?? ''));

            // If SKU is still empty after auto-generation, generate again with position
            if ($sku === '') {
                $safeOption = preg_replace('/[^a-z0-9]+/i', '-', strtolower($optionValue));
                $safeOption = trim($safeOption, '-');
                if ($safeOption === '') {
                    $safeOption = 'variant';
                }
                $sku = $productSlug.'-'.$safeOption.'-'.($pos + 1);
            }

            $image = trim((string) ($v['image'] ?? ''));
            if ($image === '') {
                $image = $fallbackImage;
            }

            $data = [
                'sku' => $sku,
                'product_id' => $productId,
                'option1_value' => $optionValue !== '' ? $optionValue : null,
                'image' => $image,
                'weight' => 0,
                'price' => $this->parsePrice($v['price'] ?? 0),
                'stock' => (int) ($v['stock'] ?? 0),
                'position' => (int) ($v['position'] ?? $pos),
                'user_id' => auth()->id(),
            ];

            if ($variantId && $existing->has($variantId)) {
                // Ensure SKU unique excluding itself
                $skuExists = Variant::where('sku', $sku)->where('id', '!=', $variantId)->exists();
                if ($skuExists) {
                    // If SKU exists, append position to make it unique
                    $baseSku = $sku;
                    $counter = 1;
                    while (Variant::where('sku', $sku)->where('id', '!=', $variantId)->exists()) {
                        $sku = $baseSku.'-'.$counter;
                        $counter++;
                    }
                    $data['sku'] = $sku;
                }
                Variant::where('id', $variantId)->where('product_id', $productId)->update($data);
                $keepIds[] = $variantId;
            } else {
                // Ensure SKU unique for new variants
                $baseSku = $sku;
                $counter = 1;
                while (Variant::where('sku', $sku)->exists()) {
                    $sku = $baseSku.'-'.$counter;
                    $counter++;
                }
                $data['sku'] = $sku;
                $new = Variant::create($data);
                $keepIds[] = $new->id;
                $createdVariants[] = $new;
            }
        }

        // Delete variants not in keep list (safe: if variant has orders -> block)
        $toDelete = $existing->keys()->diff($keepIds)->values();
        if ($toDelete->count() > 0) {
            $hasOrders = OrderDetail::whereIn('variant_id', $toDelete->all())->exists();
            if ($hasOrders) {
                throw new \InvalidArgumentException('Không thể xóa phân loại đã phát sinh đơn hàng.');
            }
            Variant::whereIn('id', $toDelete->all())->delete();
        }

        return $createdVariants;
    }

    /**
     * Sync to single/default variant (no variants mode).
     */
    private function syncSingleVariant(int $productId, array $data): void
    {
        $variants = Variant::where('product_id', $productId)->orderBy('position', 'asc')->get();
        if ($variants->count() === 0) {
            $this->createDefaultVariant($productId, [
                'sku' => $data['sku'] ?? ('SKU-'.time().'-'.rand(10, 99)),
                'image' => $data['image'] ?? null,
                'price' => $data['price'] ?? 0,
                'weight' => $data['weight'] ?? 0,
                'stock' => $data['stock'] ?? 0,
            ]);

            return;
        }

        // If there are multiple variants, don't allow disable if any has orders
        if ($variants->count() > 1) {
            $hasOrders = OrderDetail::whereIn('variant_id', $variants->pluck('id')->all())->exists();
            if ($hasOrders) {
                throw new \InvalidArgumentException('Không thể tắt phân loại vì đã có đơn hàng phát sinh theo phân loại.');
            }
            // Keep first, delete rest
            $keep = $variants->first();
            Variant::whereIn('id', $variants->pluck('id')->slice(1)->all())->delete();
            $variants = collect([$keep]);
        }

        $variant = $variants->first();
        $sku = $data['sku'] ?? $variant->sku;
        if ($sku && Variant::where('sku', $sku)->where('id', '!=', $variant->id)->exists()) {
            throw new \InvalidArgumentException('SKU đã tồn tại: '.$sku);
        }

        Variant::where('id', $variant->id)->update([
            'sku' => $sku,
            'option1_value' => null,
            'image' => $data['image'] ?? $variant->image,
            'price' => $data['price'] ?? $variant->price,
            'weight' => $data['weight'] ?? $variant->weight,
            'stock' => (int) ($data['stock'] ?? ($variant->stock ?? 0)),
            'position' => 0,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Handle slug change by creating redirection.
     */
    private function handleSlugChange(string $oldSlug, string $newSlug): void
    {
        try {
            // Check if redirection already exists
            $exists = Redirection::where('link_from', url($oldSlug))->exists();

            if (! $exists) {
                Redirection::create([
                    'link_from' => url($oldSlug),
                    'link_to' => url($newSlug),
                    'type' => 301,
                    'status' => ProductStatus::ACTIVE->value,
                    'user_id' => auth()->id(),
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't stop the process
            Log::error('Failed to create redirection: '.$e->getMessage());
        }
    }

    /**
     * Check if product has orders.
     */
    private function hasOrders(int $productId): bool
    {
        // Check if orderdetail table exists
        if (! \Illuminate\Support\Facades\Schema::hasTable('orderdetail')) {
            return false;
        }

        try {
            return OrderDetail::where('product_id', $productId)->exists();
        } catch (\Exception $e) {
            // Table doesn't exist or error, assume no orders
            return false;
        }
    }

    /**
     * Process ingredients - convert plain text to linked ingredients
     * Uses IngredientPaulas from /admin/dictionary/ingredient.
     */
    private function processIngredients(?string $content): string
    {
        if (empty($content)) {
            return '';
        }

        // If content already contains HTML links, preserve them
        if (preg_match('/<a[^>]*class=["\']item_ingredient["\'][^>]*>/i', $content)) {
            // Already processed, return as is
            return $content;
        }

        // Strip tags to work with plain text
        $cleanContent = strip_tags($content);

        // Split by comma (standard ingredient separator)
        $parts = preg_split('/,\s*/', $cleanContent);

        // Filter empty
        $parts = array_filter($parts, function ($value) {
            return trim($value) !== '';
        });

        if (empty($parts)) {
            return $content;
        }

        // Get all active ingredients from database (cache for performance)
        $ingredients = Cache::remember('ingredient_paulas_active_list', 3600, function () {
            return IngredientPaulas::where('status', '1')
                ->select('id', 'name', 'slug')
                ->get();
        });

        // Build lookup map: lowercase name => ingredient object
        $ingMap = [];
        foreach ($ingredients as $ing) {
            $lowerName = mb_strtolower(trim($ing->name), 'UTF-8');
            // Store both exact match and allow partial matching
            $ingMap[$lowerName] = $ing;
        }

        // Rebuild content with links
        $processedParts = [];
        foreach ($parts as $part) {
            $trimPart = trim($part);
            $lowerPart = mb_strtolower($trimPart, 'UTF-8');

            // Try exact match first
            if (isset($ingMap[$lowerPart])) {
                $ing = $ingMap[$lowerPart];
                // Link format: /ingredient-dictionary/{slug}
                $processedParts[] = '<a href="/ingredient-dictionary/'.htmlspecialchars($ing->slug, ENT_QUOTES, 'UTF-8').'" class="item_ingredient" data-id="'.htmlspecialchars($ing->slug, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($ing->name, ENT_QUOTES, 'UTF-8').'</a>';
            } else {
                // Try partial match (ingredient name contains the part, or vice versa)
                $matched = false;
                foreach ($ingMap as $lowerName => $ing) {
                    // Check if ingredient name contains the part, or part contains ingredient name
                    if (mb_strpos($lowerName, $lowerPart) !== false || mb_strpos($lowerPart, $lowerName) !== false) {
                        // Prefer longer matches (more specific)
                        if (! $matched || mb_strlen($ing->name) > mb_strlen($matched->name)) {
                            $matched = $ing;
                        }
                    }
                }

                if ($matched) {
                    $processedParts[] = '<a href="/ingredient-dictionary/'.htmlspecialchars($matched->slug, ENT_QUOTES, 'UTF-8').'" class="item_ingredient" data-id="'.htmlspecialchars($matched->slug, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($trimPart, ENT_QUOTES, 'UTF-8').'</a>';
                } else {
                    // No match found, keep original text
                    $processedParts[] = htmlspecialchars($trimPart, ENT_QUOTES, 'UTF-8');
                }
            }
        }

        return implode(', ', $processedParts);
    }

    /**
     * Parse price string to float.
     *
     * @param  mixed  $price
     */
    private function parsePrice($price): float
    {
        if (is_numeric($price)) {
            return (float) $price;
        }

        if (is_string($price)) {
            return (float) str_replace(',', '', $price);
        }

        return 0.0;
    }

    /**
     * Auto create import receipt for initial stock when creating product.
     *
     * @param  array  $variants  Array of Variant models
     */
    private function createInitialStockImportReceipt(Product $product, array $variants): void
    {
        try {
            Log::info('Attempting to create import receipt for new product', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'variants_count' => count($variants),
            ]);

            // Filter variants with stock > 0
            $variantsWithStock = [];
            foreach ($variants as $variant) {
                // Handle Variant model instances
                if ($variant instanceof Variant) {
                    // Reload variant to ensure we have the latest data
                    $variant->refresh();

                    $stock = (int) ($variant->stock ?? 0);
                    $variantId = $variant->id ?? null;
                    $price = (float) ($variant->price ?? 0);

                    Log::info('Checking variant for stock', [
                        'variant_id' => $variantId,
                        'stock' => $stock,
                        'price' => $price,
                        'variant_raw_stock' => $variant->getAttributes()['stock'] ?? 'N/A',
                        'variant_data' => [
                            'id' => $variant->id,
                            'sku' => $variant->sku,
                            'product_id' => $variant->product_id,
                            'stock' => $variant->stock,
                            'price' => $variant->price,
                        ],
                    ]);

                    if ($variantId && $stock > 0) {
                        $variantsWithStock[] = [
                            'variant' => $variant,
                            'stock' => $stock,
                            'price' => $price,
                        ];
                    } else {
                        Log::warning('Variant skipped - no stock or no ID', [
                            'variant_id' => $variantId,
                            'stock' => $stock,
                        ]);
                    }
                } else {
                    Log::warning('Variant is not an instance of Variant model', [
                        'type' => gettype($variant),
                        'class' => is_object($variant) ? get_class($variant) : 'N/A',
                    ]);
                }
            }

            Log::info('Filtered variants with stock', [
                'product_id' => $product->id,
                'variants_with_stock_count' => count($variantsWithStock),
            ]);

            if (empty($variantsWithStock)) {
                Log::info('No variants with stock > 0, skipping import receipt creation', [
                    'product_id' => $product->id,
                ]);

                return; // No stock to import
            }

            // Prepare import receipt items
            $items = [];
            foreach ($variantsWithStock as $item) {
                $variant = $item['variant'];
                if ($variant instanceof Variant) {
                    $items[] = [
                        'variant_id' => $variant->id,
                        'price' => $item['price'],
                        'quantity' => $item['stock'],
                    ];
                }
            }

            if (empty($items)) {
                Log::warning('No valid items prepared for import receipt', [
                    'product_id' => $product->id,
                ]);

                return;
            }

            // Generate import receipt code
            $importCode = 'NH-PRODUCT-'.$product->id.'-'.time();

            // Ensure code is unique
            $counter = 1;
            while (\App\Modules\Warehouse\Models\Warehouse::where('code', $importCode)->exists()) {
                $importCode = 'NH-PRODUCT-'.$product->id.'-'.time().'-'.$counter;
                $counter++;
            }

            Log::info('Creating import receipt via WarehouseService', [
                'product_id' => $product->id,
                'import_code' => $importCode,
                'items_count' => count($items),
                'items' => $items,
            ]);

            // Create import receipt using WarehouseService
            $warehouse = $this->warehouseService->createImportReceipt([
                'code' => $importCode,
                'subject' => 'Nhập hàng ban đầu cho sản phẩm: '.$product->name,
                'content' => 'Tự động tạo phiếu nhập hàng khi tạo sản phẩm mới',
                'vat_invoice' => '', // Không có VAT invoice cho nhập hàng ban đầu
                'items' => $items,
            ]);

            Log::info('Successfully created import receipt for new product', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'import_code' => $importCode,
                'warehouse_id' => $warehouse->id ?? null,
                'variants_count' => count($items),
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail product creation
            Log::error('Failed to auto create import receipt for new product', [
                'product_id' => $product->id,
                'product_name' => $product->name ?? 'Unknown',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
