<?php

namespace App\Services\Product;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Exceptions\ProductNotFoundException;
use App\Repositories\Product\ProductRepositoryInterface;
use App\Services\Image\ImageServiceInterface;
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
        private ImageServiceInterface $imageService
    ) {}

    /**
     * Create a new product.
     *
     * @param  array  $data  Product data
     * @return \App\Modules\Product\Models\Product
     *
     * @throws \App\Exceptions\ProductCreationException
     */
    public function createProduct(array $data): Product
    {
        DB::beginTransaction();

        try {
            // Process gallery images
            $gallery = $this->imageService->processGallery(
                $data['imageOther'] ?? [],
                $data['r2_session_key'] ?? null
            );

            // Prepare product data
            $productData = [
                'name' => $data['name'],
                'slug' => $data['slug'],
                'content' => $data['content'] ?? '',
                'description' => $data['description'] ?? '',
                'image' => $gallery[0] ?? null,
                'gallery' => json_encode($gallery),
                'cat_id' => json_encode($data['cat_id'] ?? []),
                'brand_id' => $data['brand_id'] ?? null,
                'origin_id' => $data['origin_id'] ?? null,
                'status' => ProductStatus::ACTIVE->value,
                'type' => ProductType::PRODUCT->value,
                'user_id' => auth()->id(),
            ];

            // Create product
            $product = $this->repository->create($productData);

            // Create default variant
            $this->createDefaultVariant($product, $data);

            DB::commit();

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

            throw new ProductCreationException(
                'Không thể tạo sản phẩm: '.$e->getMessage()
            );
        }
    }

    /**
     * Update an existing product.
     *
     * @param  int  $id  Product ID
     * @param  array  $data  Updated product data
     * @return \App\Modules\Product\Models\Product
     *
     * @throws \App\Exceptions\ProductNotFoundException
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
            $gallery = $this->imageService->processGallery(
                $data['imageOther'] ?? [],
                $data['r2_session_key'] ?? null
            );

            // Prepare update data
            $updateData = [
                'name' => $data['name'],
                'slug' => $data['slug'],
                'content' => $data['content'] ?? $product->content,
                'description' => $data['description'] ?? $product->description,
                'image' => $gallery[0] ?? $product->image,
                'gallery' => json_encode($gallery),
                'cat_id' => json_encode($data['cat_id'] ?? json_decode($product->cat_id ?? '[]', true)),
                'brand_id' => $data['brand_id'] ?? $product->brand_id,
                'origin_id' => $data['origin_id'] ?? $product->origin_id,
                'status' => $data['status'] ?? $product->status,
            ];

            // Update product
            $this->repository->update($id, $updateData);

            // Handle slug change (create redirection)
            if ($product->slug !== $data['slug']) {
                $this->handleSlugChange($product->slug, $data['slug']);
            }

            DB::commit();

            // Clear cache
            $this->clearProductCache($id);

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

            throw new ProductUpdateException(
                'Không thể cập nhật sản phẩm: '.$e->getMessage()
            );
        }
    }

    /**
     * Delete a product.
     *
     * @param  int  $id  Product ID
     *
     * @throws \App\Exceptions\ProductNotFoundException
     * @throws \App\Exceptions\ProductDeletionException
     */
    public function deleteProduct(int $id): bool
    {
        $product = $this->repository->find($id);

        if (! $product) {
            throw new ProductNotFoundException("Product with ID {$id} not found");
        }

        // Check if product has orders
        if ($this->hasOrders($id)) {
            throw new ProductDeletionException(
                'Không thể xóa sản phẩm đã có đơn hàng'
            );
        }

        DB::beginTransaction();

        try {
            // Delete variants first
            $product->variants()->delete();

            // Delete product
            $this->repository->delete($id);

            DB::commit();

            // Clear cache
            $this->clearProductCache($id);

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

            throw new ProductDeletionException(
                'Không thể xóa sản phẩm: '.$e->getMessage()
            );
        }
    }

    /**
     * Get product with all relations.
     *
     * @param  int  $id  Product ID
     * @return \App\Modules\Product\Models\Product
     *
     * @throws \App\Exceptions\ProductNotFoundException
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
    private function createDefaultVariant(Product $product, array $data): void
    {
        $variantService = app(VariantServiceInterface::class);

        $variantService->create([
            'product_id' => $product->id,
            'sku' => $data['sku'] ?? 'SKU-'.time().'-'.rand(10, 99),
            'image' => $product->image,
            'price' => $data['price'] ?? 0,
            'sale' => $data['sale'] ?? 0,
            'weight' => $data['weight'] ?? 0,
        ]);
    }

    /**
     * Handle slug change by creating redirection.
     */
    private function handleSlugChange(string $oldSlug, string $newSlug): void
    {
        $redirectionService = app(RedirectionServiceInterface::class);

        $redirectionService->create([
            'link_from' => url($oldSlug),
            'link_to' => url($newSlug),
            'type' => 301,
            'status' => ProductStatus::ACTIVE->value,
        ]);
    }

    /**
     * Check if product has orders.
     */
    private function hasOrders(int $productId): bool
    {
        return \App\Modules\Order\Models\OrderDetail::where('product_id', $productId)
            ->exists();
    }

    /**
     * Clear product cache.
     */
    private function clearProductCache(int $productId): void
    {
        \Illuminate\Support\Facades\Cache::forget("product:{$productId}");
        \Illuminate\Support\Facades\Cache::forget('products:list:*');
    }
}
