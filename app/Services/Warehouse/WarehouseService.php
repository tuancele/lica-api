<?php

namespace App\Services\Warehouse;

use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Models\ProductWarehouse;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Modules\FlashSale\Models\ProductSale;
use App\Modules\Deal\Models\SaleDeal;
use App\Services\Inventory\Contracts\InventoryServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

/**
 * Service class for Warehouse business logic
 * 
 * This service handles all warehouse-related business operations,
 * separating business logic from controllers and data access.
 */
class WarehouseService implements WarehouseServiceInterface
{
    public function __construct(private InventoryServiceInterface $inventory)
    {
    }
    /**
     * Get inventory list with filters
     * 
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getInventory(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $hasNewStockColumns =
            Schema::hasColumn('product_warehouse', 'physical_stock') &&
            Schema::hasColumn('product_warehouse', 'flash_sale_stock') &&
            Schema::hasColumn('product_warehouse', 'deal_stock');

        if ($hasNewStockColumns) {
            // Only consider rows that actually carry snapshot columns (physical_stock not null)
            $latestPwSub = '(select max(pw2.id) from product_warehouse as pw2 where pw2.variant_id = variants.id and pw2.physical_stock is not null)';
            $activeFlashSaleQtySub = '(select COALESCE(sum(ps.number - ps.buy),0) from productsales ps join flashsales fs on fs.id = ps.flashsale_id where ps.variant_id = variants.id and fs.status = 1 and fs.start <= UNIX_TIMESTAMP() and fs.end >= UNIX_TIMESTAMP())';
            $activeDealQtySub = "(select COALESCE(sum(ds.qty - COALESCE(ds.buy, 0)),0) from deal_sales ds join deals d on d.id = ds.deal_id where d.status = 1 and d.start <= UNIX_TIMESTAMP() and d.end >= UNIX_TIMESTAMP() and ds.status = 1 and ds.product_id = posts.id and ((ds.variant_id is not null and ds.variant_id = variants.id) or (ds.variant_id is null)))";

            $query = Variant::select(
                'variants.id as variant_id',
                'variants.sku as variant_sku',
                'variants.option1_value as variant_option',
                'posts.id as product_id',
                'posts.name as product_name',
                'posts.image as product_image',
                DB::raw('COALESCE(pw.physical_stock, variants.stock, 0) as physical_stock'),
                DB::raw("({$activeFlashSaleQtySub}) as flash_sale_stock_val"), // Alias mới để tránh xung đột
                DB::raw("({$activeDealQtySub}) as deal_stock_val"),
                DB::raw("GREATEST(COALESCE(pw.physical_stock, variants.stock, 0) - ({$activeFlashSaleQtySub}) - ({$activeDealQtySub}), 0) as available_stock_val")
            )
            ->join('posts', 'posts.id', '=', 'variants.product_id')
            ->leftJoin('product_warehouse as pw', function ($join) use ($latestPwSub) {
                $join->on('pw.variant_id', '=', 'variants.id')
                    ->whereRaw("pw.id = ({$latestPwSub})");
            })
            ->where('posts.type', 'product')
            ->where('posts.status', 1);
        } else {
            // Fallback for environments where migration hasn't been executed yet.
            // Keep API stable (no 500), and return stock based on legacy import-export totals.
            $query = Variant::select(
                'variants.id as variant_id',
                'variants.sku as variant_sku',
                'variants.option1_value as variant_option',
                'posts.id as product_id',
                'posts.name as product_name',
                'posts.image as product_image'
            )
            ->join('posts', 'posts.id', '=', 'variants.product_id')
            ->where('posts.type', 'product')
            ->where('posts.status', 1);

            $query->selectRaw('
                COALESCE(SUM(CASE WHEN pw_import.qty IS NOT NULL THEN pw_import.qty ELSE 0 END), 0) as import_total,
                COALESCE(SUM(CASE WHEN pw_export.qty IS NOT NULL THEN pw_export.qty ELSE 0 END), 0) as export_total,
                MAX(CASE WHEN pw_import.created_at IS NOT NULL THEN pw_import.created_at END) as last_import_date,
                MAX(CASE WHEN pw_export.created_at IS NOT NULL THEN pw_export.created_at END) as last_export_date,
                (COALESCE(SUM(CASE WHEN pw_import.qty IS NOT NULL THEN pw_import.qty ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN pw_export.qty IS NOT NULL THEN pw_export.qty ELSE 0 END), 0)) as available_stock,
                (COALESCE(SUM(CASE WHEN pw_import.qty IS NOT NULL THEN pw_import.qty ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN pw_export.qty IS NOT NULL THEN pw_export.qty ELSE 0 END), 0)) as physical_stock,
                0 as flash_sale_stock,
                0 as deal_stock
            ')
            ->leftJoin('product_warehouse as pw_import', function($join) {
                $join->on('pw_import.variant_id', '=', 'variants.id')
                     ->where('pw_import.type', '=', 'import');
            })
            ->leftJoin('product_warehouse as pw_export', function($join) {
                $join->on('pw_export.variant_id', '=', 'variants.id')
                     ->where('pw_export.type', '=', 'export');
            })
            ->groupBy('variants.id', 'variants.sku', 'variants.option1_value', 'posts.id', 'posts.name', 'posts.image');
        }

        // Filter by keyword
        if (isset($filters['keyword']) && !empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function($q) use ($keyword) {
                $q->where('posts.name', 'like', "%{$keyword}%")
                  ->orWhere('variants.sku', 'like', "%{$keyword}%");
            });
        }

        // Filter by variant_id
        if (isset($filters['variant_id']) && !empty($filters['variant_id'])) {
            $query->where('variants.id', $filters['variant_id']);
        }

        // Filter by product_id
        if (isset($filters['product_id']) && !empty($filters['product_id'])) {
            $query->where('posts.id', $filters['product_id']);
        }

        // Filter by stock range
        if (isset($filters['min_stock'])) {
            if ($hasNewStockColumns) {
                $query->whereRaw('COALESCE(pw.qty, 0) >= ?', [$filters['min_stock']]);
            } else {
                $query->havingRaw('available_stock >= ?', [$filters['min_stock']]);
            }
        }
        if (isset($filters['max_stock'])) {
            if ($hasNewStockColumns) {
                $query->whereRaw('COALESCE(pw.qty, 0) <= ?', [$filters['max_stock']]);
            } else {
                $query->havingRaw('available_stock <= ?', [$filters['max_stock']]);
            }
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'product_name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        
        if ($sortBy === 'stock') {
            if ($hasNewStockColumns) {
                $query->orderByRaw("COALESCE(pw.qty, 0) {$sortOrder}");
            } else {
                $query->orderByRaw("available_stock {$sortOrder}");
            }
        } elseif ($sortBy === 'variant_name') {
            $query->orderBy('variants.option1_value', $sortOrder);
        } else {
            $query->orderBy('posts.name', $sortOrder);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get inventory detail for a variant
     * 
     * @param int $variantId
     * @return array
     */
    public function getVariantInventory(int $variantId): array
    {
        $variant = Variant::with(['product'])->findOrFail($variantId);
        
        $stockSnapshot = $this->getStockSnapshot($variantId);
        $importTotal = $stockSnapshot['import_total'];
        $exportTotal = $stockSnapshot['export_total'];
        $currentStock = $stockSnapshot['available'];

        // Get import history
        $importHistory = ProductWarehouse::where('variant_id', $variantId)
            ->where('type', 'import')
            ->with(['warehouse'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'receipt_id' => $item->warehouse_id,
                    'receipt_code' => $item->warehouse?->code ?? '',
                    'quantity' => $item->qty ?? 0,
                    'price' => $item->price ?? 0,
                    'date' => $item->created_at?->toISOString(),
                ];
            });

        // Get export history
        $exportHistory = ProductWarehouse::where('variant_id', $variantId)
            ->where('type', 'export')
            ->with(['warehouse'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'receipt_id' => $item->warehouse_id,
                    'receipt_code' => $item->warehouse?->code ?? '',
                    'quantity' => $item->qty ?? 0,
                    'price' => $item->price ?? 0,
                    'date' => $item->created_at?->toISOString(),
                ];
            });

        return [
            'variant_id' => $variant->id,
            'variant_sku' => $variant->sku,
            'variant_option' => $variant->option1_value ?? 'Mặc định',
            'product_id' => $variant->product_id,
            'product_name' => $variant->product?->name ?? '',
            'product_image' => $variant->product?->image ?? null,
            'import_total' => $importTotal,
            'export_total' => $exportTotal,
            'current_stock' => $currentStock,
            'import_history' => $importHistory,
            'export_history' => $exportHistory,
            'last_import_date' => ProductWarehouse::where('variant_id', $variantId)
                ->where('type', 'import')
                ->max('created_at')?->toISOString(),
            'last_export_date' => ProductWarehouse::where('variant_id', $variantId)
                ->where('type', 'export')
                ->max('created_at')?->toISOString(),
        ];
    }

    /**
     * Get import receipts list with filters
     * 
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getImportReceipts(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Warehouse::with(['user', 'items.variant.product'])
            ->where('type', 'import');

        // Filter by keyword
        if (isset($filters['keyword']) && !empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                  ->orWhere('subject', 'like', "%{$keyword}%");
            });
        }

        // Filter by code
        if (isset($filters['code']) && !empty($filters['code'])) {
            $query->where('code', $filters['code']);
        }

        // Filter by user_id
        if (isset($filters['user_id']) && !empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filter by date range
        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get import receipt detail with items
     * 
     * @param int $id
     * @return Warehouse
     */
    public function getImportReceipt(int $id): Warehouse
    {
        return Warehouse::with(['user', 'items.variant.product'])
            ->where('type', 'import')
            ->findOrFail($id);
    }

    /**
     * Create a new import receipt
     * 
     * @param array $data
     * @return Warehouse
     */
    public function createImportReceipt(array $data): Warehouse
    {
        DB::beginTransaction();
        
        try {
            // Combine VAT invoice and content
            $content = $data['content'] ?? '';
            if (isset($data['vat_invoice']) && !empty($data['vat_invoice'])) {
                $content = ($content ? $content . "\n" : '') . 'Số hóa đơn VAT: ' . $data['vat_invoice'];
            }

            // Create warehouse record
            $warehouse = Warehouse::create([
                'code' => $data['code'],
                'subject' => $data['subject'],
                'content' => $content,
                'type' => 'import',
                'user_id' => Auth::id() ?? 1,
            ]);

            // Create product warehouse items
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $variantId = (int)$item['variant_id'];
                    $qty = (int)($item['quantity'] ?? 0);
                    $price = (float)($item['price'] ?? 0);

                    // 1. Tạo bản ghi nhật ký nhập kho
                    ProductWarehouse::create([
                        'warehouse_id' => $warehouse->id,
                        'variant_id' => $variantId,
                        'price' => $price,
                        'qty' => $qty,
                        'type' => 'import',
                        'created_at' => now(),
                    ]);

                    // 2. Cập nhật physical_stock thông qua InventoryService
                    // Hệ thống mới cần snapshot tồn kho thực tế
                    app(\App\Services\Inventory\InventoryServiceInterface::class)->importStock($variantId, $qty, 'warehouse_import: ' . $warehouse->code);
                }
            }

            DB::commit();
            
            // Reload with relations
            return $warehouse->load(['user', 'items.variant.product']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Create import receipt failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing import receipt
     * 
     * @param int $id
     * @param array $data
     * @return Warehouse
     */
    public function updateImportReceipt(int $id, array $data): Warehouse
    {
        DB::beginTransaction();
        
        try {
            $warehouse = Warehouse::where('type', 'import')->findOrFail($id);

            // Combine VAT invoice and content
            $content = $data['content'] ?? $warehouse->content;
            if (isset($data['vat_invoice']) && !empty($data['vat_invoice'])) {
                // Remove old VAT invoice if exists
                $content = preg_replace('/Số hóa đơn VAT:\s*.+/i', '', $content);
                $content = trim($content);
                $content = ($content ? $content . "\n" : '') . 'Số hóa đơn VAT: ' . $data['vat_invoice'];
            }

            // Update warehouse record
            if (isset($data['code'])) {
                $warehouse->code = $data['code'];
            }
            if (isset($data['subject'])) {
                $warehouse->subject = $data['subject'];
            }
            $warehouse->content = $content;
            $warehouse->save();

            // Update items if provided
            if (isset($data['items']) && is_array($data['items'])) {
                // Delete old items
                ProductWarehouse::where('warehouse_id', $warehouse->id)->delete();
                
                // Create new items
                foreach ($data['items'] as $item) {
                    ProductWarehouse::create([
                        'warehouse_id' => $warehouse->id,
                        'variant_id' => $item['variant_id'],
                        'price' => $item['price'] ?? 0,
                        'qty' => $item['quantity'] ?? 0,
                        'type' => 'import',
                    ]);
                }
            }

            DB::commit();
            
            // Reload with relations
            return $warehouse->load(['user', 'items.variant.product']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update import receipt failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete an import receipt
     * 
     * @param int $id
     * @return bool
     */
    public function deleteImportReceipt(int $id): bool
    {
        DB::beginTransaction();
        
        try {
            $warehouse = Warehouse::where('type', 'import')->findOrFail($id);
            
            // Delete items first
            ProductWarehouse::where('warehouse_id', $warehouse->id)->delete();
            
            // Delete warehouse record
            $warehouse->delete();
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete import receipt failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get export receipts list with filters
     * 
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getExportReceipts(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Warehouse::with(['user', 'items.variant.product'])
            ->where('type', 'export');

        // Filter by keyword
        if (isset($filters['keyword']) && !empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                  ->orWhere('subject', 'like', "%{$keyword}%");
            });
        }

        // Filter by code
        if (isset($filters['code']) && !empty($filters['code'])) {
            $query->where('code', $filters['code']);
        }

        // Filter by user_id
        if (isset($filters['user_id']) && !empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filter by date range
        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get export receipt detail with items
     * 
     * @param int $id
     * @return Warehouse
     */
    public function getExportReceipt(int $id): Warehouse
    {
        return Warehouse::with(['user', 'items.variant.product'])
            ->where('type', 'export')
            ->findOrFail($id);
    }

    /**
     * Create a new export receipt
     * 
     * @param array $data
     * @return Warehouse
     * @throws \Exception
     */
    public function createExportReceipt(array $data): Warehouse
    {
        DB::beginTransaction();
        
        try {
            // Validate stock availability
            $stockErrors = [];
            foreach ($data['items'] ?? [] as $index => $item) {
                $variantId = $item['variant_id'] ?? null;
                $quantity = $item['quantity'] ?? 0;
                
                if ($variantId) {
                    $stockSnapshot = $this->getStockSnapshot($variantId);
                    $availableStock = $stockSnapshot['sellable'];
                    
                    if ($quantity > $availableStock) {
                        $stockErrors["items.{$index}.quantity"] = [
                            "Số lượng vượt quá tồn kho. Tồn kho hiện tại: {$availableStock}"
                        ];
                    }
                }
            }
            
            if (!empty($stockErrors)) {
                DB::rollBack();
                throw new \Exception(json_encode(['errors' => $stockErrors]));
            }

            // Combine VAT invoice and content
            $content = $data['content'] ?? '';
            if (isset($data['vat_invoice']) && !empty($data['vat_invoice'])) {
                $content = ($content ? $content . "\n" : '') . 'Số hóa đơn VAT: ' . $data['vat_invoice'];
            }

            // Create warehouse record
            $warehouse = Warehouse::create([
                'code' => $data['code'],
                'subject' => $data['subject'],
                'content' => $content,
                'type' => 'export',
                'user_id' => Auth::id(),
            ]);

            // Create product warehouse items
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    ProductWarehouse::create([
                        'warehouse_id' => $warehouse->id,
                        'variant_id' => $item['variant_id'],
                        'price' => $item['price'] ?? 0,
                        'qty' => $item['quantity'] ?? 0,
                        'type' => 'export',
                    ]);
                }
            }

            DB::commit();
            
            // Reload with relations
            return $warehouse->load(['user', 'items.variant.product']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Create export receipt failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing export receipt
     * 
     * @param int $id
     * @param array $data
     * @return Warehouse
     * @throws \Exception
     */
    public function updateExportReceipt(int $id, array $data): Warehouse
    {
        DB::beginTransaction();
        
        try {
            $warehouse = Warehouse::where('type', 'export')->findOrFail($id);

            // Validate stock availability if items are being updated
            if (isset($data['items']) && is_array($data['items'])) {
                $stockErrors = [];
                foreach ($data['items'] as $index => $item) {
                    $variantId = $item['variant_id'] ?? null;
                    $quantity = $item['quantity'] ?? 0;
                    
                    if ($variantId) {
                        $stockSnapshot = $this->getStockSnapshot($variantId);
                        
                        // Subtract current receipt's export quantity
                        $currentReceiptExport = ProductWarehouse::where('warehouse_id', $warehouse->id)
                            ->where('variant_id', $variantId)
                            ->where('type', 'export')
                            ->sum('qty');
                        
                        $availableStock = max(0, $stockSnapshot['sellable'] + $currentReceiptExport);
                        
                        if ($quantity > $availableStock) {
                            $stockErrors["items.{$index}.quantity"] = [
                                "Số lượng vượt quá tồn kho. Tồn kho hiện tại: {$availableStock}"
                            ];
                        }
                    }
                }
                
                if (!empty($stockErrors)) {
                    DB::rollBack();
                    throw new \Exception(json_encode(['errors' => $stockErrors]));
                }
            }

            // Combine VAT invoice and content
            $content = $data['content'] ?? $warehouse->content;
            if (isset($data['vat_invoice']) && !empty($data['vat_invoice'])) {
                // Remove old VAT invoice if exists
                $content = preg_replace('/Số hóa đơn VAT:\s*.+/i', '', $content);
                $content = trim($content);
                $content = ($content ? $content . "\n" : '') . 'Số hóa đơn VAT: ' . $data['vat_invoice'];
            }

            // Update warehouse record
            if (isset($data['code'])) {
                $warehouse->code = $data['code'];
            }
            if (isset($data['subject'])) {
                $warehouse->subject = $data['subject'];
            }
            $warehouse->content = $content;
            $warehouse->save();

            // Update items if provided
            if (isset($data['items']) && is_array($data['items'])) {
                // Delete old items
                ProductWarehouse::where('warehouse_id', $warehouse->id)->delete();
                
                // Create new items
                foreach ($data['items'] as $item) {
                    ProductWarehouse::create([
                        'warehouse_id' => $warehouse->id,
                        'variant_id' => $item['variant_id'],
                        'price' => $item['price'] ?? 0,
                        'qty' => $item['quantity'] ?? 0,
                        'type' => 'export',
                    ]);
                }
            }

            DB::commit();
            
            // Reload with relations
            return $warehouse->load(['user', 'items.variant.product']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update export receipt failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete an export receipt
     * 
     * @param int $id
     * @return bool
     */
    public function deleteExportReceipt(int $id): bool
    {
        DB::beginTransaction();
        
        try {
            $warehouse = Warehouse::where('type', 'export')->findOrFail($id);
            
            // Delete items first
            ProductWarehouse::where('warehouse_id', $warehouse->id)->delete();
            
            // Delete warehouse record
            $warehouse->delete();
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete export receipt failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Search products by keyword
     * 
     * @param string $keyword
     * @param int $limit
     * @return array
     */
    public function searchProducts(string $keyword, int $limit = 50): array
    {
        if (strlen($keyword) < 2) {
            return [];
        }

        $products = Product::select('id', 'name', 'slug', 'image')
            ->where('type', 'product')
            ->where('status', 1)
            ->where('name', 'like', "%{$keyword}%")
            ->orderBy('name', 'asc')
            ->limit($limit)
            ->get();

        return $products->map(function($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'image' => $product->image,
            ];
        })->toArray();
    }

    /**
     * Get variants for a product
     * 
     * @param int $productId
     * @return array
     */
    public function getProductVariants(int $productId): array
    {
        $product = Product::where('type', 'product')->findOrFail($productId);

        $variants = Variant::select('id', 'sku', 'product_id', 'option1_value', 'price', 'stock')
            ->where('product_id', $productId)
            ->orderBy('option1_value', 'asc')
            ->get();

        // Auto-create a fallback variant for single products so warehouse import/export can work.
        if ($variants->isEmpty() && (int) $product->has_variants === 0) {
            $fallbackSku = $product->slug
                ? 'SKU-' . strtoupper(str_replace(' ', '-', $product->slug))
                : 'PROD-' . $productId . '-DEFAULT';

            $fallbackVariant = Variant::firstOrCreate(
                ['product_id' => $productId],
                [
                    'sku' => $fallbackSku,
                    'option1_value' => $product->option1_name ?: 'Default',
                    'price' => (float) ($product->getAttribute('price') ?? 0),
                    'stock' => (int) ($product->getAttribute('stock') ?? 0),
                    'position' => 0,
                    'user_id' => Auth::id(),
                ]
            );

            $fallbackVariant->setAttribute('is_default_variant', true);
            $variants = collect([$fallbackVariant]);
        }

        $hasNewStockColumns =
            Schema::hasColumn('product_warehouse', 'physical_stock') &&
            Schema::hasColumn('product_warehouse', 'flash_sale_stock') &&
            Schema::hasColumn('product_warehouse', 'deal_stock');

        return $variants->map(function($variant) use ($hasNewStockColumns) {
            $isDefault = (bool) $variant->getAttribute('is_default_variant');

            if ($hasNewStockColumns) {
                $latestWarehouseRow = ProductWarehouse::where('variant_id', $variant->id)
                    ->latest('id')
                    ->first();

                $physicalStock = $latestWarehouseRow ? (int) ($latestWarehouseRow->physical_stock ?? 0) : 0;
                $flashSaleStock = $latestWarehouseRow ? (int) ($latestWarehouseRow->flash_sale_stock ?? 0) : 0;
                $dealStock = $latestWarehouseRow ? (int) ($latestWarehouseRow->deal_stock ?? 0) : 0;
                $availableStock = max(0, $physicalStock - $flashSaleStock - $dealStock);
            } else {
                $stockSnapshot = $this->getStockSnapshot($variant->id);
                $physicalStock = $stockSnapshot['physical'];
                $flashSaleStock = $stockSnapshot['flash'];
                $dealStock = $stockSnapshot['deal'];
                $availableStock = $stockSnapshot['available'];
            }
            
            return [
                'id' => $variant->id,
                'product_id' => $variant->product_id,
                'sku' => $variant->sku,
                'option1_value' => $variant->option1_value ?? 'Default',
                'physical_stock' => $physicalStock,
                'flash_sale_stock' => $flashSaleStock,
                'deal_stock' => $dealStock,
                'current_stock' => $availableStock,
                'is_default' => $isDefault,
            ];
        })->toArray();
    }

    /**
     * Get stock information for a variant
     * 
     * @param int $variantId
     * @return array
     */
    public function getVariantStock(int $variantId): array
    {
        // Ensure variant id is integer
        $variantId = (int) $variantId;

        // Load variant
        $variant = Variant::findOrFail($variantId);

        // Warehouse V2 only - no legacy fallback
        // Will try SKU fallback if variant_id not found

        $activeFlashSaleQtySub = ProductSale::query()
            ->selectRaw('COALESCE(SUM(productsales.number),0)')
            ->join('flashsales as fs', 'fs.id', '=', 'productsales.flashsale_id')
            ->whereColumn('productsales.variant_id', 'variants.id');

        $hasNewStockColumns =
            Schema::hasColumn('product_warehouse', 'physical_stock') &&
            Schema::hasColumn('product_warehouse', 'flash_sale_stock') &&
            Schema::hasColumn('product_warehouse', 'deal_stock');

        $physicalStock = 0;
        $flashSaleStock = 0;
        $dealStock = 0;
        $availableStock = 0;

        if ($hasNewStockColumns) {
            // V2: Use physical_stock from inventory_stocks table (single source of truth)
            // Warehouse interface uses InventoryService which queries inventory_stocks
            // We need to use the same table for consistency
            $variantSku = $variant->sku ?? '';

            // Step 1: Query physical_stock from inventory_stocks by variant_id (main warehouse only)
            $inventoryStock = \App\Models\InventoryStock::where('variant_id', $variantId)
                ->where('warehouse_id', 1)
                ->first();

            $physicalStock = 0;
            $flashSaleStock = 0;
            $dealStock = 0;
            $availableStock = 0;

            if ($inventoryStock) {
                $physicalStock = (int) ($inventoryStock->physical_stock ?? 0);
                $flashSaleStock = (int) ($inventoryStock->flash_sale_hold ?? 0);
                $dealStock = (int) ($inventoryStock->deal_hold ?? 0);
                $availableStock = (int) ($inventoryStock->available_stock ?? 0);
            } else {
                // If not found, try SKU fallback
                if (!empty($variantSku)) {
                    $inventoryStockBySku = \App\Models\InventoryStock::query()
                        ->join('variants', 'variants.id', '=', 'inventory_stocks.variant_id')
                        ->where('variants.sku', $variantSku)
                        ->where('inventory_stocks.warehouse_id', 1)
                        ->select('inventory_stocks.*')
                        ->first();

                    if ($inventoryStockBySku) {
                        $physicalStock = (int) ($inventoryStockBySku->physical_stock ?? 0);
                        $flashSaleStock = (int) ($inventoryStockBySku->flash_sale_hold ?? 0);
                        $dealStock = (int) ($inventoryStockBySku->deal_hold ?? 0);
                        $availableStock = (int) ($inventoryStockBySku->available_stock ?? 0);
                    }
                }
            }

            Log::info('WarehouseService: Query physical_stock from inventory_stocks', [
                'variant_id' => $variantId,
                'variant_sku' => $variantSku,
                'warehouse_id' => 1,
                'physical_stock' => $physicalStock,
                'flash_sale_hold' => $flashSaleStock,
                'deal_hold' => $dealStock,
                'available_stock' => $availableStock,
                'row_found' => $inventoryStock !== null,
            ]);

            // W2 TRUTH: Log raw physical_stock value for verification
            Log::info('W2 TRUTH: SKU=' . $variantSku . ' | Physical=' . $physicalStock, [
                'variant_id' => $variantId,
                'variant_sku' => $variantSku,
                'physical_stock' => $physicalStock,
                'flash_sale_hold' => $flashSaleStock,
                'deal_hold' => $dealStock,
                'available_stock' => $availableStock,
            ]);

            // Note: flashSaleStock and dealStock are already loaded from inventory_stocks above
            // availableStock is also already calculated from inventory_stocks
            // No need to query ProductSale/SaleDeal again
        } else {
            $stockSnapshot = $this->getStockSnapshot($variantId);
            $physicalStock = $stockSnapshot['physical'];
            $flashSaleStock = (int) ProductSale::query()
                ->join('flashsales as fs', 'fs.id', '=', 'productsales.flashsale_id')
                ->where('productsales.variant_id', $variantId)
                ->where('fs.status', 1)
                ->where('fs.start', '<=', time())
                ->where('fs.end', '>=', time())
                ->selectRaw('SUM(number - buy) as total')
                ->value('total') ?? 0;
            $dealStock = $stockSnapshot['deal'];
            $availableStock = max(0, $stockSnapshot['available'] - $flashSaleStock - $dealStock);
        }

        $importAvgPrice = ProductWarehouse::where('variant_id', $variantId)
            ->where('type', 'import')
            ->where('price', '>', 0)
            ->avg('price');

        $exportAvgPrice = ProductWarehouse::where('variant_id', $variantId)
            ->where('type', 'export')
            ->where('price', '>', 0)
            ->avg('price');

        return [
            'variant_id' => $variant->id,
            'variant_sku' => $variant->sku,
            'variant_option' => $variant->option1_value ?? 'Mặc định',
            'physical_stock' => $physicalStock,
            'flash_sale_stock' => $flashSaleStock,
            'deal_stock' => $dealStock,
            'available_stock' => $availableStock,
            'current_stock' => $availableStock,
            'price' => [
                'import_avg' => $importAvgPrice ? (float) $importAvgPrice : null,
                'export_avg' => $exportAvgPrice ? (float) $exportAvgPrice : null,
            ],
        ];
    }

    /**
     * Get suggested price for a variant
     * 
     * @param int $variantId
     * @param string $type
     * @return array
     */
    public function getVariantPrice(int $variantId, string $type = 'export'): array
    {
        $variant = Variant::findOrFail($variantId);
        
        $suggestedPrice = null;
        $lastPrice = null;
        
        if ($type === 'export') {
            // For export, use base price only (do not use legacy "sale" field)
            $suggestedPrice = $variant->price;
            
            // Get last export price
            $lastExport = ProductWarehouse::where('variant_id', $variantId)
                ->where('type', 'export')
                ->where('price', '>', 0)
                ->orderBy('created_at', 'desc')
                ->first();
            
            $lastPrice = $lastExport?->price;
        } else {
            // For import, use last import price
            $lastImport = ProductWarehouse::where('variant_id', $variantId)
                ->where('type', 'import')
                ->where('price', '>', 0)
                ->orderBy('created_at', 'desc')
                ->first();
            
            $suggestedPrice = $lastImport?->price ?? $variant->price;
            $lastPrice = $lastImport?->price;
        }

        return [
            'variant_id' => $variant->id,
            'suggested_price' => $suggestedPrice ? (float) $suggestedPrice : null,
            'price_type' => $type,
            'last_price' => $lastPrice ? (float) $lastPrice : null,
            'variant_price' => $variant->price ? (float) $variant->price : null,
            'variant_sale' => null,
        ];
    }

    /**
     * Get quantity statistics
     * 
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getQuantityStatistics(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Variant::select(
            'variants.id as variant_id',
            'variants.sku as variant_sku',
            'variants.option1_value as variant_option',
            'posts.id as product_id',
            'posts.name as product_name'
        )
        ->join('posts', 'posts.id', '=', 'variants.product_id')
        ->where('posts.type', 'product');

        // Filter by keyword
        if (isset($filters['keyword']) && !empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function($q) use ($keyword) {
                $q->where('posts.name', 'like', "%{$keyword}%")
                  ->orWhere('variants.sku', 'like', "%{$keyword}%");
            });
        }

        // Get import/export totals
        $query->selectRaw('
            COALESCE(SUM(CASE WHEN pw_import.qty IS NOT NULL THEN pw_import.qty ELSE 0 END), 0) as import_total,
            COALESCE(SUM(CASE WHEN pw_export.qty IS NOT NULL THEN pw_export.qty ELSE 0 END), 0) as export_total
        ')
        ->leftJoin('product_warehouse as pw_import', function($join) {
            $join->on('pw_import.variant_id', '=', 'variants.id')
                 ->where('pw_import.type', '=', 'import');
        })
        ->leftJoin('product_warehouse as pw_export', function($join) {
            $join->on('pw_export.variant_id', '=', 'variants.id')
                 ->where('pw_export.type', '=', 'export');
        })
        ->groupBy('variants.id', 'variants.sku', 'variants.option1_value', 'posts.id', 'posts.name');

        // Sort
        $sortBy = $filters['sort_by'] ?? 'product_name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        
        if ($sortBy === 'stock') {
            $query->orderByRaw("(import_total - export_total) {$sortOrder}");
        } else {
            $query->orderBy('posts.name', $sortOrder);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get revenue statistics
     * 
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getRevenueStatistics(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Variant::select(
            'variants.id as variant_id',
            'variants.sku as variant_sku',
            'variants.option1_value as variant_option',
            'posts.id as product_id',
            'posts.name as product_name'
        )
        ->join('posts', 'posts.id', '=', 'variants.product_id')
        ->where('posts.type', 'product');

        // Filter by keyword
        if (isset($filters['keyword']) && !empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function($q) use ($keyword) {
                $q->where('posts.name', 'like', "%{$keyword}%")
                  ->orWhere('variants.sku', 'like', "%{$keyword}%");
            });
        }

        // Get import/export values and quantities
        $query->selectRaw('
            COALESCE(SUM(CASE WHEN pw_import.price * pw_import.qty IS NOT NULL THEN pw_import.price * pw_import.qty ELSE 0 END), 0) as import_value,
            COALESCE(SUM(CASE WHEN pw_export.price * pw_export.qty IS NOT NULL THEN pw_export.price * pw_export.qty ELSE 0 END), 0) as export_value,
            COALESCE(SUM(CASE WHEN pw_import.qty IS NOT NULL THEN pw_import.qty ELSE 0 END), 0) as import_quantity,
            COALESCE(SUM(CASE WHEN pw_export.qty IS NOT NULL THEN pw_export.qty ELSE 0 END), 0) as export_quantity
        ')
        ->leftJoin('product_warehouse as pw_import', function($join) {
            $join->on('pw_import.variant_id', '=', 'variants.id')
                 ->where('pw_import.type', '=', 'import');
        })
        ->leftJoin('product_warehouse as pw_export', function($join) {
            $join->on('pw_export.variant_id', '=', 'variants.id')
                 ->where('pw_export.type', '=', 'export');
        })
        ->groupBy('variants.id', 'variants.sku', 'variants.option1_value', 'posts.id', 'posts.name');

        // Sort
        $sortBy = $filters['sort_by'] ?? 'product_name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy('posts.name', $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get warehouse summary statistics
     * 
     * @param array $filters
     * @return array
     */
    public function getSummaryStatistics(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        // Total products and variants
        $totalProducts = Product::where('type', 'product')->where('status', 1)->count();
        $totalVariants = Variant::join('posts', 'posts.id', '=', 'variants.product_id')
            ->where('posts.type', 'product')
            ->where('posts.status', 1)
            ->count();

        // Receipt counts
        $importReceiptsQuery = Warehouse::where('type', 'import');
        $exportReceiptsQuery = Warehouse::where('type', 'export');
        
        if ($dateFrom) {
            $importReceiptsQuery->whereDate('created_at', '>=', $dateFrom);
            $exportReceiptsQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $importReceiptsQuery->whereDate('created_at', '<=', $dateTo);
            $exportReceiptsQuery->whereDate('created_at', '<=', $dateTo);
        }
        
        $totalImportReceipts = $importReceiptsQuery->count();
        $totalExportReceipts = $exportReceiptsQuery->count();

        // Total values
        $importValueQuery = ProductWarehouse::where('type', 'import')
            ->selectRaw('SUM(price * qty) as total');
        $exportValueQuery = ProductWarehouse::where('type', 'export')
            ->selectRaw('SUM(price * qty) as total');
        
        if ($dateFrom) {
            $importValueQuery->whereDate('created_at', '>=', $dateFrom);
            $exportValueQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $importValueQuery->whereDate('created_at', '<=', $dateTo);
            $exportValueQuery->whereDate('created_at', '<=', $dateTo);
        }
        
        $totalImportValue = $importValueQuery->first()->total ?? 0;
        $totalExportValue = $exportValueQuery->first()->total ?? 0;
        $totalProfit = $totalExportValue - $totalImportValue;

        // Total quantities
        $importQuantityQuery = ProductWarehouse::where('type', 'import')
            ->selectRaw('SUM(qty) as total');
        $exportQuantityQuery = ProductWarehouse::where('type', 'export')
            ->selectRaw('SUM(qty) as total');
        
        if ($dateFrom) {
            $importQuantityQuery->whereDate('created_at', '>=', $dateFrom);
            $exportQuantityQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $importQuantityQuery->whereDate('created_at', '<=', $dateTo);
            $exportQuantityQuery->whereDate('created_at', '<=', $dateTo);
        }
        
        $totalImportQuantity = $importQuantityQuery->first()->total ?? 0;
        $totalExportQuantity = $exportQuantityQuery->first()->total ?? 0;

        // Current stock value (approximate)
        $currentStockValue = 0;
        $variants = Variant::join('posts', 'posts.id', '=', 'variants.product_id')
            ->where('posts.type', 'product')
            ->where('posts.status', 1)
            ->get();
        
        foreach ($variants as $variant) {
            $stockSnapshot = $this->getStockSnapshot($variant->id);
            $currentStock = $stockSnapshot['available'];
            
            // Use average import price for stock value calculation
            $avgPrice = ProductWarehouse::where('variant_id', $variant->id)
                ->where('type', 'import')
                ->where('price', '>', 0)
                ->avg('price');
            
            if ($avgPrice) {
                $currentStockValue += $currentStock * $avgPrice;
            }
        }

        // Low stock items (stock < 10)
        $lowStockItems = 0;
        $outOfStockItems = 0;
        
        foreach ($variants as $variant) {
            $stockSnapshot = $this->getStockSnapshot($variant->id);
            $currentStock = $stockSnapshot['available'];
            
            if ($currentStock === 0) {
                $outOfStockItems++;
            } elseif ($currentStock < 10) {
                $lowStockItems++;
            }
        }

        return [
            'total_products' => $totalProducts,
            'total_variants' => $totalVariants,
            'total_import_receipts' => $totalImportReceipts,
            'total_export_receipts' => $totalExportReceipts,
            'total_import_value' => (float) $totalImportValue,
            'total_export_value' => (float) $totalExportValue,
            'total_profit' => (float) $totalProfit,
            'total_import_quantity' => (int) $totalImportQuantity,
            'total_export_quantity' => (int) $totalExportQuantity,
            'current_stock_value' => (float) $currentStockValue,
            'low_stock_items' => $lowStockItems,
            'out_of_stock_items' => $outOfStockItems,
        ];
    }

    /**
     * Deduct stock for a variant (create export receipt automatically)
     * 
     * @param int $variantId Variant ID (or Product ID if no variant)
     * @param int $quantity Quantity to deduct
     * @param string $reason Reason for deduction (e.g., 'flashsale_order', 'normal_order')
     * @return bool
     */
    public function deductStock(int $variantId, int $quantity, string $reason = 'order'): bool
    {
        DB::beginTransaction();
        
        try {
            // Check if variant exists
            $variant = Variant::find($variantId);
            if (!$variant) {
                // If not found as variant, try to find default variant for product
                $product = Product::find($variantId);
                if ($product) {
                    $variant = $product->variant($variantId);
                }
            }
            
            if (!$variant) {
                throw new \Exception("Variant hoặc Product không tồn tại: {$variantId}");
            }
            
            // Check available stock
            $stockInfo = $this->getVariantStock($variant->id);
            if ($stockInfo['current_stock'] < $quantity) {
                throw new \Exception("Không đủ tồn kho. Tồn kho hiện tại: {$stockInfo['current_stock']}, Yêu cầu: {$quantity}");
            }
            
            // Create export receipt automatically
            $code = 'XH-' . strtoupper($reason) . '-' . $variant->id . '-' . time();
            $subject = "Xuất hàng tự động - " . $reason;
            $content = "Tự động tạo phiếu xuất hàng khi xử lý đơn hàng. Lý do: {$reason}";
            
            $warehouse = Warehouse::create([
                'code' => $code,
                'subject' => $subject,
                'content' => $content,
                'type' => 'export',
                'user_id' => Auth::id() ?? 1,
            ]);
            
            // Get variant price for export (base price only)
            $variantPrice = $variant->price;
            
            // Create product warehouse item
            ProductWarehouse::create([
                'warehouse_id' => $warehouse->id,
                'variant_id' => $variant->id,
                'price' => $variantPrice,
                'qty' => $quantity,
                'type' => 'export',
            ]);
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Deduct stock failed: ' . $e->getMessage(), [
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'reason' => $reason
            ]);
            throw $e;
        }
    }

    /**
     * Snapshot stock data from InventoryService for legacy callers.
     */
    private function getStockSnapshot(int $variantId): array
    {
        $dto = $this->inventory->getStock($variantId);

        $physical = (int) ($dto->physicalStock ?? 0);
        $reserved = (int) ($dto->reservedStock ?? 0);
        $flash = (int) ($dto->flashSaleHold ?? 0);
        $deal = (int) ($dto->dealHold ?? 0);
        $available = (int) ($dto->availableStock ?? max(0, $physical - $reserved));
        $sellable = (int) ($dto->sellableStock ?? max(0, $available - $flash - $deal));

        return [
            'physical' => $physical,
            'reserved' => $reserved,
            'flash' => $flash,
            'deal' => $deal,
            'available' => $available,
            'sellable' => $sellable,
            'import_total' => $physical + $reserved,
            'export_total' => $reserved,
        ];
    }

    /**
     * Process stock deduction for an order
     * Centralized stock management for Warehouse V2
     * 
     * @param int $orderId Order ID
     * @return bool Success status
     * @throws \Exception
     */
    public function processOrderStock(int $orderId): bool
    {
        try {
            $order = \App\Modules\Order\Models\Order::findOrFail($orderId);
            $orderDetails = \App\Modules\Order\Models\OrderDetail::where('order_id', $orderId)->get();

            if ($orderDetails->isEmpty()) {
                Log::warning('WarehouseService: processOrderStock - No order details found', [
                    'order_id' => $orderId,
                ]);
                return false;
            }

            $now = time();
            $processedItems = [];

            foreach ($orderDetails as $detail) {
                $variantId = (int) ($detail->variant_id ?? 0);
                $quantity = (int) ($detail->qty ?? 0);
                $productId = (int) ($detail->product_id ?? 0);
                $dealsaleId = $detail->dealsale_id ?? null;
                $productsaleId = $detail->productsale_id ?? null;

                if ($variantId <= 0 || $quantity <= 0) {
                    Log::warning('WarehouseService: processOrderStock - Invalid variant_id or quantity', [
                        'order_id' => $orderId,
                        'order_detail_id' => $detail->id,
                        'variant_id' => $variantId,
                        'quantity' => $quantity,
                    ]);
                    continue;
                }

                // Get inventory stock record
                $inventoryStock = \App\Models\InventoryStock::where('variant_id', $variantId)
                    ->where('warehouse_id', 1)
                    ->lockForUpdate()
                    ->first();

                if (!$inventoryStock) {
                    Log::error('WarehouseService: processOrderStock - Inventory stock not found', [
                        'order_id' => $orderId,
                        'variant_id' => $variantId,
                    ]);
                    throw new \Exception("Tồn kho không tìm thấy cho variant_id: {$variantId}");
                }

                // Check available stock
                $availableStock = (int) ($inventoryStock->available_stock ?? 0);
                if ($availableStock < $quantity) {
                    Log::error('WarehouseService: processOrderStock - Insufficient stock', [
                        'order_id' => $orderId,
                        'variant_id' => $variantId,
                        'required' => $quantity,
                        'available' => $availableStock,
                    ]);
                    throw new \Exception("Tồn kho không đủ cho variant_id: {$variantId}. Yêu cầu: {$quantity}, Có sẵn: {$availableStock}");
                }

                // Determine product type: Flash Sale, Deal, or Normal
                $isFlashSale = false;
                $isDeal = false;
                $productSaleId = null;
                $saleDealId = null;
                $activeFlashSale = null;
                $saleDeal = null;

                // Check if Flash Sale (use productsale_id from OrderDetail if available, otherwise query)
                if ($productsaleId) {
                    $activeFlashSale = ProductSale::find($productsaleId);
                    if ($activeFlashSale) {
                        $flashSale = $activeFlashSale->flashsale ?? null;
                        if ($flashSale && $flashSale->status == '1' && $flashSale->start <= $now && $flashSale->end >= $now) {
                            $isFlashSale = true;
                            $productSaleId = $productsaleId;
                        }
                    }
                } else {
                    // Fallback: Query active Flash Sale by variant_id
                    $activeFlashSale = ProductSale::query()
                        ->join('flashsales as fs', 'fs.id', '=', 'productsales.flashsale_id')
                        ->where('productsales.variant_id', $variantId)
                        ->where('fs.status', '1')
                        ->where('fs.start', '<=', $now)
                        ->where('fs.end', '>=', $now)
                        ->first();

                    if ($activeFlashSale) {
                        $isFlashSale = true;
                        $productSaleId = $activeFlashSale->id;
                    }
                }

                // Check if Deal (use dealsale_id from OrderDetail)
                if ($dealsaleId) {
                    $saleDeal = \App\Modules\Deal\Models\SaleDeal::find($dealsaleId);
                    if ($saleDeal) {
                        $deal = $saleDeal->deal ?? null;
                        if ($deal && $deal->status == '1' && $deal->start <= $now && $deal->end >= $now) {
                            $isDeal = true;
                            $saleDealId = $dealsaleId;
                        }
                    }
                }

                // Case 1: Normal Product - Just deduct physical_stock
                // Note: available_stock is a generated column, will be auto-calculated by MySQL
                if (!$isFlashSale && !$isDeal) {
                    $inventoryStock->decrement('physical_stock', $quantity);
                    $inventoryStock->update(['last_movement_at' => now()]);

                    Log::info('WarehouseService: processOrderStock - Normal product stock deducted', [
                        'order_id' => $orderId,
                        'variant_id' => $variantId,
                        'quantity' => $quantity,
                        'physical_stock_after' => $inventoryStock->fresh()->physical_stock,
                    ]);
                }
                // Case 2: Flash Sale Product - Deduct physical_stock + increment ProductSale.buy
                // Note: available_stock is a generated column, will be auto-calculated by MySQL
                elseif ($isFlashSale && $activeFlashSale) {
                    $inventoryStock->decrement('physical_stock', $quantity);
                    $inventoryStock->update(['last_movement_at' => now()]);

                    // Increment ProductSale.buy for real-time tracking
                    $activeFlashSale->increment('buy', $quantity);

                    Log::info('WarehouseService: processOrderStock - Flash Sale product stock deducted', [
                        'order_id' => $orderId,
                        'variant_id' => $variantId,
                        'quantity' => $quantity,
                        'product_sale_id' => $productSaleId,
                        'physical_stock_after' => $inventoryStock->fresh()->physical_stock,
                        'flash_sale_buy_before' => $activeFlashSale->buy - $quantity,
                        'flash_sale_buy_after' => $activeFlashSale->fresh()->buy,
                    ]);
                }
                // Case 3: Deal Product - Deduct physical_stock + increment SaleDeal.buy (if not already done)
                // Note: SaleDeal.buy is already incremented in CartController, but we ensure it here for consistency
                // Note: available_stock is a generated column, will be auto-calculated by MySQL
                elseif ($isDeal && $saleDeal) {
                    $inventoryStock->decrement('physical_stock', $quantity);
                    $inventoryStock->update(['last_movement_at' => now()]);

                    // Ensure SaleDeal.buy is incremented (may already be done in CartController, but ensure consistency)
                    $buyBefore = $saleDeal->buy;
                    $saleDeal->increment('buy', $quantity);

                    Log::info('WarehouseService: processOrderStock - Deal product stock deducted', [
                        'order_id' => $orderId,
                        'variant_id' => $variantId,
                        'quantity' => $quantity,
                        'sale_deal_id' => $saleDealId,
                        'physical_stock_after' => $inventoryStock->fresh()->physical_stock,
                        'deal_buy_before' => $buyBefore,
                        'deal_buy_after' => $saleDeal->fresh()->buy,
                    ]);
                }

                $processedItems[] = [
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                    'type' => $isFlashSale ? 'flash_sale' : ($isDeal ? 'deal' : 'normal'),
                ];
            }

            Log::info('WarehouseService: processOrderStock - Completed', [
                'order_id' => $orderId,
                'processed_items_count' => count($processedItems),
                'processed_items' => $processedItems,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('WarehouseService: processOrderStock - Error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Rollback stock for a cancelled order
     * Reverse stock deduction when order is cancelled
     * 
     * @param int $orderId Order ID
     * @return bool Success status
     * @throws \Exception
     */
    public function rollbackOrderStock(int $orderId): bool
    {
        try {
            $order = \App\Modules\Order\Models\Order::findOrFail($orderId);
            $orderDetails = \App\Modules\Order\Models\OrderDetail::where('order_id', $orderId)->get();

            if ($orderDetails->isEmpty()) {
                Log::warning('WarehouseService: rollbackOrderStock - No order details found', [
                    'order_id' => $orderId,
                ]);
                return false;
            }

            $now = time();
            $rolledBackItems = [];

            foreach ($orderDetails as $detail) {
                $variantId = (int) ($detail->variant_id ?? 0);
                $quantity = (int) ($detail->qty ?? 0);
                $dealsaleId = $detail->dealsale_id ?? null;
                $productsaleId = $detail->productsale_id ?? null;

                if ($variantId <= 0 || $quantity <= 0) {
                    continue;
                }

                // Get inventory stock record
                $inventoryStock = \App\Models\InventoryStock::where('variant_id', $variantId)
                    ->where('warehouse_id', 1)
                    ->lockForUpdate()
                    ->first();

                if (!$inventoryStock) {
                    Log::warning('WarehouseService: rollbackOrderStock - Inventory stock not found', [
                        'order_id' => $orderId,
                        'variant_id' => $variantId,
                    ]);
                    continue;
                }

                // Determine product type from OrderDetail
                $isFlashSale = false;
                $isDeal = false;
                $productSaleId = null;
                $saleDealId = null;
                $activeFlashSale = null;
                $saleDeal = null;

                // Check if Flash Sale (use productsale_id from OrderDetail if available)
                if ($productsaleId) {
                    $activeFlashSale = ProductSale::find($productsaleId);
                    if ($activeFlashSale) {
                        $isFlashSale = true;
                        $productSaleId = $productsaleId;
                    }
                } else {
                    // Fallback: Query active Flash Sale by variant_id
                    $activeFlashSale = ProductSale::query()
                        ->join('flashsales as fs', 'fs.id', '=', 'productsales.flashsale_id')
                        ->where('productsales.variant_id', $variantId)
                        ->where('fs.status', '1')
                        ->where('fs.start', '<=', $now)
                        ->where('fs.end', '>=', $now)
                        ->first();

                    if ($activeFlashSale) {
                        $isFlashSale = true;
                        $productSaleId = $activeFlashSale->id;
                    }
                }

                // Check if Deal (use dealsale_id from OrderDetail)
                if ($dealsaleId) {
                    $saleDeal = \App\Modules\Deal\Models\SaleDeal::find($dealsaleId);
                    if ($saleDeal) {
                        $deal = $saleDeal->deal ?? null;
                        if ($deal && $deal->status == '1' && $deal->start <= $now && $deal->end >= $now) {
                            $isDeal = true;
                            $saleDealId = $dealsaleId;
                        }
                    }
                }

                // Rollback: Add back physical_stock
                // Note: available_stock is a generated column, will be auto-calculated by MySQL
                $inventoryStock->increment('physical_stock', $quantity);
                $inventoryStock->update(['last_movement_at' => now()]);

                // Rollback Flash Sale: Decrement ProductSale.buy
                if ($isFlashSale && $activeFlashSale) {
                    $buyBefore = $activeFlashSale->buy;
                    $activeFlashSale->decrement('buy', $quantity);
                    Log::info('WarehouseService: rollbackOrderStock - Flash Sale buy decremented', [
                        'order_id' => $orderId,
                        'variant_id' => $variantId,
                        'quantity' => $quantity,
                        'product_sale_id' => $productSaleId,
                        'flash_sale_buy_before' => $buyBefore,
                        'flash_sale_buy_after' => $activeFlashSale->fresh()->buy,
                    ]);
                }

                // Rollback Deal: Decrement SaleDeal.buy
                if ($isDeal && $saleDeal) {
                    $buyBefore = $saleDeal->buy;
                    $saleDeal->decrement('buy', $quantity);
                    Log::info('WarehouseService: rollbackOrderStock - Deal buy decremented', [
                        'order_id' => $orderId,
                        'variant_id' => $variantId,
                        'quantity' => $quantity,
                        'sale_deal_id' => $saleDealId,
                        'deal_buy_before' => $buyBefore,
                        'deal_buy_after' => $saleDeal->fresh()->buy,
                    ]);
                }

                Log::info('WarehouseService: rollbackOrderStock - Stock rolled back', [
                    'order_id' => $orderId,
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                    'physical_stock_after' => $inventoryStock->fresh()->physical_stock,
                ]);

                $rolledBackItems[] = [
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                    'type' => $isFlashSale ? 'flash_sale' : ($isDeal ? 'deal' : 'normal'),
                ];
            }

            Log::info('WarehouseService: rollbackOrderStock - Completed', [
                'order_id' => $orderId,
                'rolled_back_items_count' => count($rolledBackItems),
                'rolled_back_items' => $rolledBackItems,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('WarehouseService: rollbackOrderStock - Error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
