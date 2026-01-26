<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Events\Inventory\LowStockDetected;
use App\Events\Inventory\OutOfStockDetected;
use App\Events\Inventory\StockExported;
use App\Events\Inventory\StockImported;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidReservationException;
use App\Models\InventoryStock;
use App\Models\StockAlert;
use App\Models\StockMovement;
use App\Models\StockReceipt;
use App\Models\StockReceiptItem;
use App\Models\StockReservation;
use App\Models\WarehouseV2;
use App\Services\Inventory\Contracts\InventoryServiceInterface as InventoryV2ServiceInterface;
use App\Services\Inventory\DTOs\AdjustStockDTO;
use App\Services\Inventory\DTOs\ExportStockDTO;
use App\Services\Inventory\DTOs\ImportStockDTO;
use App\Services\Inventory\DTOs\ReserveStockDTO;
use App\Services\Inventory\DTOs\StockDTO;
use App\Services\Inventory\DTOs\TransferStockDTO;
use App\Services\Inventory\InventoryServiceInterface as LegacyInventoryServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService implements InventoryV2ServiceInterface, LegacyInventoryServiceInterface
{
    public function getStock(int $variantId, ?int $warehouseId = null): StockDTO
    {
        $warehouseId = $warehouseId ?? $this->getDefaultWarehouseId();
        $cacheKey = $this->getCacheKey($variantId, $warehouseId);

        if (config('inventory.cache.enabled', true)) {
            return Cache::remember($cacheKey, config('inventory.cache.ttl_seconds', 60), function () use ($variantId, $warehouseId) {
                return $this->fetchStock($variantId, $warehouseId);
            });
        }

        return $this->fetchStock($variantId, $warehouseId);
    }

    private function fetchStock(int $variantId, int $warehouseId): StockDTO
    {
        $stock = InventoryStock::where('warehouse_id', $warehouseId)
            ->where('variant_id', $variantId)->first();

        return $stock ? StockDTO::fromModel($stock) : StockDTO::empty($variantId, $warehouseId);
    }

    public function getStockBatch(array $variantIds, ?int $warehouseId = null): Collection
    {
        $warehouseId = $warehouseId ?? $this->getDefaultWarehouseId();
        $stocks = InventoryStock::where('warehouse_id', $warehouseId)
            ->whereIn('variant_id', $variantIds)->get()->keyBy('variant_id');

        return collect($variantIds)->map(
            fn ($id) => $stocks->has($id) ? StockDTO::fromModel($stocks->get($id)) : StockDTO::empty($id, $warehouseId)
        );
    }

    public function isAvailable(int $variantId, int $quantity, ?int $warehouseId = null): bool
    {
        $stock = $this->getStock($variantId, $warehouseId);

        return $stock->sellableStock >= $quantity;
    }

    public function checkAvailabilityBatch(array $items, ?int $warehouseId = null): array
    {
        $variantIds = array_column($items, 'variant_id');
        $stocks = $this->getStockBatch($variantIds, $warehouseId)->keyBy('variantId');

        return array_map(fn ($item) => [
            'variant_id' => $item['variant_id'],
            'requested' => $item['quantity'],
            'available' => $stocks->get($item['variant_id'])?->sellableStock ?? 0,
            'is_available' => ($stocks->get($item['variant_id'])?->sellableStock ?? 0) >= $item['quantity'],
        ], $items);
    }

    public function getLowStockItems(?int $warehouseId = null): Collection
    {
        $query = InventoryStock::with(['variant.product', 'warehouse'])->lowStock();
        if ($warehouseId) {
            $query->forWarehouse($warehouseId);
        }

        return $query->get();
    }

    public function getOutOfStockItems(?int $warehouseId = null): Collection
    {
        $query = InventoryStock::with(['variant.product', 'warehouse'])->outOfStock();
        if ($warehouseId) {
            $query->forWarehouse($warehouseId);
        }

        return $query->get();
    }

    public function import(ImportStockDTO $data): StockReceipt
    {
        return DB::transaction(function () use ($data) {
            $receipt = StockReceipt::create([
                'receipt_code' => StockReceipt::generateCode('import'),
                'type' => 'import', 'status' => 'completed',
                'to_warehouse_id' => $data->warehouseId,
                'supplier_id' => $data->supplierId, 'supplier_name' => $data->supplierName,
                'subject' => $data->subject, 'content' => $data->content,
                'vat_invoice' => $data->vatInvoice, 'created_by' => $data->createdBy,
                'completed_at' => now(), 'completed_by' => $data->createdBy,
            ]);

            $totalItems = 0;
            $totalQuantity = 0;
            $totalValue = 0;

            foreach ($data->items as $item) {
                $stock = $this->lockStock($item['variant_id'], $data->warehouseId);
                $stockBefore = $stock->physical_stock;
                $quantity = (int) $item['quantity'];
                $unitPrice = (float) ($item['unit_price'] ?? 0);

                $stock->increment('physical_stock', $quantity);
                if ($unitPrice > 0) {
                    $stock->updateAverageCost($quantity, $unitPrice);
                }
                $stock->update(['last_movement_at' => now()]);
                $stockAfter = $stock->fresh()->physical_stock;

                StockReceiptItem::create([
                    'receipt_id' => $receipt->id, 'variant_id' => $item['variant_id'],
                    'quantity' => $quantity, 'unit_price' => $unitPrice,
                    'stock_before' => $stockBefore, 'stock_after' => $stockAfter,
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                ]);

                $this->recordMovement([
                    'warehouse_id' => $data->warehouseId, 'variant_id' => $item['variant_id'],
                    'movement_type' => 'import', 'quantity' => $quantity,
                    'physical_before' => $stockBefore, 'physical_after' => $stockAfter,
                    'reserved_before' => $stock->reserved_stock, 'reserved_after' => $stock->reserved_stock,
                    'available_before' => $stockBefore - $stock->reserved_stock,
                    'available_after' => $stockAfter - $stock->reserved_stock,
                    'reference_type' => 'receipt', 'reference_id' => $receipt->id,
                    'reference_code' => $receipt->receipt_code, 'created_by' => $data->createdBy,
                ]);

                $this->clearStockCache($item['variant_id'], $data->warehouseId);
                StockAlert::autoResolve($data->warehouseId, $item['variant_id'], $stockAfter, $stock->low_stock_threshold);

                $totalItems++;
                $totalQuantity += $quantity;
                $totalValue += ($quantity * $unitPrice);
            }

            $receipt->update(['total_items' => $totalItems, 'total_quantity' => $totalQuantity, 'total_value' => $totalValue]);
            event(new StockImported($receipt));

            return $receipt->fresh(['items.variant.product', 'toWarehouse', 'creator']);
        });
    }

    public function export(ExportStockDTO $data): StockReceipt
    {
        return DB::transaction(function () use ($data) {
            foreach ($data->items as $item) {
                $stock = $this->getStock($item['variant_id'], $data->warehouseId);
                if ($stock->availableStock < $item['quantity']) {
                    throw new InsufficientStockException("Insufficient stock for variant {$item['variant_id']}");
                }
            }

            $receipt = StockReceipt::create([
                'receipt_code' => StockReceipt::generateCode('export'),
                'type' => 'export', 'status' => 'completed',
                'from_warehouse_id' => $data->warehouseId,
                'customer_id' => $data->customerId, 'customer_name' => $data->customerName,
                'reference_type' => $data->referenceType, 'reference_id' => $data->referenceId,
                'subject' => $data->subject, 'content' => $data->content,
                'vat_invoice' => $data->vatInvoice, 'created_by' => $data->createdBy,
                'completed_at' => now(), 'completed_by' => $data->createdBy,
            ]);

            $totalItems = 0;
            $totalQuantity = 0;
            $totalValue = 0;

            foreach ($data->items as $item) {
                $stock = $this->lockStock($item['variant_id'], $data->warehouseId);
                $stockBefore = $stock->physical_stock;
                $quantity = (int) $item['quantity'];
                $unitPrice = (float) ($item['unit_price'] ?? 0);

                $stock->decrement('physical_stock', $quantity);
                $stock->update(['last_movement_at' => now()]);
                $stockAfter = $stock->fresh()->physical_stock;

                StockReceiptItem::create([
                    'receipt_id' => $receipt->id, 'variant_id' => $item['variant_id'],
                    'quantity' => $quantity, 'unit_price' => $unitPrice,
                    'stock_before' => $stockBefore, 'stock_after' => $stockAfter,
                ]);

                $this->recordMovement([
                    'warehouse_id' => $data->warehouseId, 'variant_id' => $item['variant_id'],
                    'movement_type' => 'export', 'quantity' => -$quantity,
                    'physical_before' => $stockBefore, 'physical_after' => $stockAfter,
                    'reserved_before' => $stock->reserved_stock, 'reserved_after' => $stock->reserved_stock,
                    'available_before' => $stockBefore - $stock->reserved_stock,
                    'available_after' => $stockAfter - $stock->reserved_stock,
                    'reference_type' => 'receipt', 'reference_id' => $receipt->id,
                    'reference_code' => $receipt->receipt_code, 'created_by' => $data->createdBy,
                ]);

                $this->clearStockCache($item['variant_id'], $data->warehouseId);
                $this->checkAndCreateAlerts($stock->fresh());

                $totalItems++;
                $totalQuantity += $quantity;
                $totalValue += ($quantity * $unitPrice);
            }

            $receipt->update(['total_items' => $totalItems, 'total_quantity' => $totalQuantity, 'total_value' => $totalValue]);
            event(new StockExported($receipt));

            return $receipt->fresh(['items.variant.product', 'fromWarehouse', 'creator']);
        });
    }

    public function transfer(TransferStockDTO $data): StockReceipt
    {
        return DB::transaction(function () use ($data) {
            foreach ($data->items as $item) {
                $stock = $this->getStock($item['variant_id'], $data->fromWarehouseId);
                if ($stock->availableStock < $item['quantity']) {
                    throw new InsufficientStockException('Insufficient stock in source warehouse');
                }
            }

            $receipt = StockReceipt::create([
                'receipt_code' => StockReceipt::generateCode('transfer'),
                'type' => 'transfer', 'status' => 'completed',
                'from_warehouse_id' => $data->fromWarehouseId,
                'to_warehouse_id' => $data->toWarehouseId,
                'subject' => $data->subject ?? 'Chuyển kho',
                'created_by' => $data->createdBy, 'completed_at' => now(),
            ]);

            foreach ($data->items as $item) {
                $quantity = (int) $item['quantity'];

                // Deduct from source
                $source = $this->lockStock($item['variant_id'], $data->fromWarehouseId);
                $source->decrement('physical_stock', $quantity);

                // Add to destination
                $dest = $this->lockStock($item['variant_id'], $data->toWarehouseId);
                $dest->increment('physical_stock', $quantity);

                StockReceiptItem::create([
                    'receipt_id' => $receipt->id, 'variant_id' => $item['variant_id'],
                    'quantity' => $quantity, 'unit_price' => $source->average_cost,
                ]);

                $this->clearStockCache($item['variant_id'], $data->fromWarehouseId);
                $this->clearStockCache($item['variant_id'], $data->toWarehouseId);
            }

            return $receipt->fresh(['items.variant.product', 'fromWarehouse', 'toWarehouse']);
        });
    }

    public function adjust(AdjustStockDTO $data): StockReceipt
    {
        return DB::transaction(function () use ($data) {
            $receipt = StockReceipt::create([
                'receipt_code' => StockReceipt::generateCode('adjustment'),
                'type' => 'adjustment', 'status' => 'completed',
                'to_warehouse_id' => $data->warehouseId,
                'subject' => $data->subject ?? 'Điều chỉnh tồn kho',
                'created_by' => $data->createdBy, 'completed_at' => now(),
            ]);

            foreach ($data->items as $item) {
                $stock = $this->lockStock($item['variant_id'], $data->warehouseId);
                $stockBefore = $stock->physical_stock;
                $newQuantity = (int) $item['new_quantity'];

                $stock->update(['physical_stock' => $newQuantity, 'last_stock_check' => now()]);

                StockReceiptItem::create([
                    'receipt_id' => $receipt->id, 'variant_id' => $item['variant_id'],
                    'quantity' => abs($newQuantity - $stockBefore),
                    'stock_before' => $stockBefore, 'stock_after' => $newQuantity,
                ]);

                $this->clearStockCache($item['variant_id'], $data->warehouseId);
            }

            return $receipt->fresh(['items.variant.product', 'toWarehouse']);
        });
    }

    public function reserve(ReserveStockDTO $data): StockReservation
    {
        return DB::transaction(function () use ($data) {
            $warehouseId = $data->warehouseId ?? $this->getDefaultWarehouseId();
            $stock = $this->lockStock($data->variantId, $warehouseId);

            $available = $stock->physical_stock - $stock->reserved_stock - $stock->flash_sale_hold - $stock->deal_hold;
            if ($available < $data->quantity) {
                throw new InsufficientStockException("Cannot reserve {$data->quantity} units. Available: {$available}");
            }

            $stock->increment('reserved_stock', $data->quantity);

            $reservation = StockReservation::create([
                'warehouse_id' => $warehouseId, 'variant_id' => $data->variantId,
                'quantity' => $data->quantity, 'reference_type' => $data->referenceType,
                'reference_id' => $data->referenceId, 'reference_code' => $data->referenceCode,
                'status' => 'active', 'expires_at' => $data->expiresAt,
            ]);

            $this->clearStockCache($data->variantId, $warehouseId);

            return $reservation;
        });
    }

    public function reserveBatch(array $items): Collection
    {
        $reservations = collect();
        DB::transaction(function () use ($items, &$reservations) {
            foreach ($items as $item) {
                $dto = $item instanceof ReserveStockDTO ? $item : ReserveStockDTO::fromArray($item);
                $reservations->push($this->reserve($dto));
            }
        });

        return $reservations;
    }

    public function confirmReservation(int $reservationId): bool
    {
        return DB::transaction(function () use ($reservationId) {
            $reservation = StockReservation::lockForUpdate()->findOrFail($reservationId);
            if ($reservation->status !== 'active') {
                throw new InvalidReservationException('Reservation is not active');
            }

            $stock = $this->lockStock($reservation->variant_id, $reservation->warehouse_id);
            $stock->decrement('physical_stock', $reservation->quantity);
            $stock->decrement('reserved_stock', $reservation->quantity);

            $reservation->update(['status' => 'confirmed', 'confirmed_at' => now()]);
            $this->clearStockCache($reservation->variant_id, $reservation->warehouse_id);
            $this->checkAndCreateAlerts($stock->fresh());

            return true;
        });
    }

    public function releaseReservation(int $reservationId, ?int $userId = null, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($reservationId, $userId, $reason) {
            $reservation = StockReservation::lockForUpdate()->findOrFail($reservationId);
            if ($reservation->status !== 'active') {
                return false;
            }

            $stock = $this->lockStock($reservation->variant_id, $reservation->warehouse_id);
            $stock->decrement('reserved_stock', $reservation->quantity);

            $reservation->update([
                'status' => 'released', 'released_at' => now(),
                'released_by' => $userId, 'release_reason' => $reason,
            ]);

            $this->clearStockCache($reservation->variant_id, $reservation->warehouse_id);

            return true;
        });
    }

    public function releaseReservationsByReference(string $referenceType, int $referenceId): int
    {
        $reservations = StockReservation::active()->forReference($referenceType, $referenceId)->get();
        $count = 0;
        foreach ($reservations as $r) {
            if ($this->releaseReservation($r->id)) {
                $count++;
            }
        }

        return $count;
    }

    public function releaseExpiredReservations(): int
    {
        $expired = StockReservation::expired()->get();
        $count = 0;

        foreach ($expired as $reservation) {
            try {
                DB::transaction(function () use ($reservation) {
                    $stock = $this->lockStock($reservation->variant_id, $reservation->warehouse_id);
                    $stock->decrement('reserved_stock', $reservation->quantity);
                    $reservation->update(['status' => 'expired', 'released_at' => now()]);
                    $this->clearStockCache($reservation->variant_id, $reservation->warehouse_id);
                });
                $count++;
            } catch (\Exception $e) {
                Log::error("Failed to release expired reservation #{$reservation->id}: ".$e->getMessage());
            }
        }

        return $count;
    }

    public function deductForOrder(int $orderId): bool
    {
        $reservations = StockReservation::active()->forReference('order', $orderId)->get();
        foreach ($reservations as $r) {
            $this->confirmReservation($r->id);
        }

        return true;
    }

    public function restoreForOrder(int $orderId): bool
    {
        $reservations = StockReservation::forReference('order', $orderId)->get();
        foreach ($reservations as $r) {
            if ($r->status === 'active') {
                $this->releaseReservation($r->id);
            } elseif ($r->status === 'confirmed') {
                DB::transaction(function () use ($r) {
                    $stock = $this->lockStock($r->variant_id, $r->warehouse_id);
                    $stock->increment('physical_stock', $r->quantity);
                    $r->update(['status' => 'released', 'released_at' => now()]);
                    $this->clearStockCache($r->variant_id, $r->warehouse_id);
                });
            }
        }

        return true;
    }

    public function processReturn(int $orderId, array $items): StockReceipt
    {
        return $this->import(new ImportStockDTO(
            code: "RTN-{$orderId}",
            subject: "Trả hàng từ đơn #{$orderId}",
            warehouseId: $this->getDefaultWarehouseId(),
            items: $items,
            createdBy: auth()->id() ?? 1,
        ));
    }

    public function getMovementHistory(int $variantId, array $filters = []): Collection
    {
        $query = StockMovement::with(['warehouse', 'creator'])->forVariant($variantId)->orderBy('created_at', 'desc');
        if (isset($filters['warehouse_id'])) {
            $query->forWarehouse($filters['warehouse_id']);
        }
        if (isset($filters['limit'])) {
            $query->limit($filters['limit']);
        }

        return $query->get();
    }

    public function getInventoryValuation(?int $warehouseId = null): array
    {
        $query = InventoryStock::with(['variant.product', 'warehouse']);
        if ($warehouseId) {
            $query->forWarehouse($warehouseId);
        }
        $stocks = $query->get();

        $totalValue = $stocks->sum(fn ($s) => $s->physical_stock * $s->average_cost);

        return [
            'total_value' => $totalValue,
            'total_items' => $stocks->sum('physical_stock'),
            'sku_count' => $stocks->count(),
        ];
    }

    public function getStockSummary(int $variantId, string $startDate, string $endDate, ?int $warehouseId = null): array
    {
        return StockMovement::getSummary($variantId, $warehouseId ?? $this->getDefaultWarehouseId(), $startDate, $endDate);
    }

    // Helper methods
    private function getDefaultWarehouseId(): int
    {
        return Cache::remember('default_warehouse_id', 3600, fn () => WarehouseV2::where('is_default', true)->value('id') ?? 1);
    }

    private function lockStock(int $variantId, int $warehouseId): InventoryStock
    {
        return InventoryStock::lockForUpdate()->firstOrCreate(
            ['warehouse_id' => $warehouseId, 'variant_id' => $variantId],
            ['physical_stock' => 0, 'reserved_stock' => 0, 'low_stock_threshold' => 10]
        );
    }

    private function recordMovement(array $data): StockMovement
    {
        $data['created_at'] = now();
        $data['ip_address'] = request()->ip();
        $data['created_by'] = $data['created_by'] ?? auth()->id();

        return StockMovement::create($data);
    }

    private function getCacheKey(int $variantId, int $warehouseId): string
    {
        return "inventory_stock:{$warehouseId}:{$variantId}";
    }

    private function clearStockCache(int $variantId, int $warehouseId): void
    {
        Cache::forget($this->getCacheKey($variantId, $warehouseId));
    }

    private function checkAndCreateAlerts(InventoryStock $stock): void
    {
        if ($stock->physical_stock <= 0) {
            StockAlert::createIfNotExists($stock->warehouse_id, $stock->variant_id, 'out_of_stock', $stock->physical_stock);
            event(new OutOfStockDetected($stock));
        } elseif ($stock->physical_stock <= $stock->low_stock_threshold) {
            StockAlert::createIfNotExists($stock->warehouse_id, $stock->variant_id, 'low_stock', $stock->physical_stock, $stock->low_stock_threshold);
            event(new LowStockDetected($stock));
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Legacy InventoryServiceInterface adapters
    |--------------------------------------------------------------------------
    |
    | Keep compatibility for existing order/flash sale/deal code that depends
    | on App\Services\Inventory\InventoryServiceInterface.
    |
    */

    public function processOrder(array $orderItems): array
    {
        $results = [];
        foreach ($orderItems as $item) {
            $variantId = (int) ($item['variant_id'] ?? 0);
            $qty = (int) ($item['quantity'] ?? 0);
            if ($variantId <= 0 || $qty <= 0) {
                continue;
            }
            $results[] = $this->deductStockForOrder($variantId, $qty, (string) ($item['reason'] ?? 'order'));
        }

        return $results;
    }

    public function getAvailableStock(int $productId, ?int $variantId = null): int
    {
        if ($variantId === null) {
            $variantId = (int) \DB::table('variants')->where('product_id', $productId)->value('id');
        }
        if (! $variantId) {
            return 0;
        }

        return (int) $this->getStock((int) $variantId)->sellableStock;
    }

    public function validateFlashSaleStock(int $productId, ?int $variantId, int $flashStockLimit): array
    {
        $available = $this->getAvailableStock($productId, $variantId);
        if ($available < $flashStockLimit) {
            return ['success' => false, 'message' => "Insufficient stock. Available: {$available}, required: {$flashStockLimit}"];
        }

        return ['success' => true, 'message' => 'OK'];
    }

    public function allocateStockForPromotion(int $variantId, int $quantity, string $type): array
    {
        if ($quantity <= 0) {
            return ['success' => false, 'message' => 'Invalid quantity'];
        }

        $warehouseId = $this->getDefaultWarehouseId();

        return DB::transaction(function () use ($variantId, $quantity, $type, $warehouseId) {
            $stock = $this->lockStock($variantId, $warehouseId);
            $dto = $this->getStock($variantId, $warehouseId);

            if ($dto->sellableStock < $quantity) {
                throw new InsufficientStockException("Insufficient sellable stock for promotion. Available: {$dto->sellableStock}");
            }

            $field = $type === 'deal' ? 'deal_hold' : 'flash_sale_hold';
            $movementType = $type === 'deal' ? 'deal_hold' : 'flash_sale_hold';

            $before = (int) $stock->{$field};
            $stock->increment($field, $quantity);
            $stock->update(['last_movement_at' => now()]);

            $this->recordMovement([
                'warehouse_id' => $warehouseId,
                'variant_id' => $variantId,
                'movement_type' => $movementType,
                'quantity' => $quantity,
                'physical_before' => $stock->physical_stock,
                'physical_after' => $stock->physical_stock,
                'reserved_before' => $stock->reserved_stock,
                'reserved_after' => $stock->reserved_stock,
                'available_before' => $dto->availableStock,
                'available_after' => $this->getStock($variantId, $warehouseId)->availableStock,
                'reason' => "Promotion hold ({$type})",
            ]);

            $this->clearStockCache($variantId, $warehouseId);

            return ['success' => true, 'message' => 'OK', 'before' => $before, 'after' => $before + $quantity];
        });
    }

    public function releaseStockFromPromotion(int $variantId, int $quantity, string $type): array
    {
        if ($quantity <= 0) {
            return ['success' => false, 'message' => 'Invalid quantity'];
        }

        $warehouseId = $this->getDefaultWarehouseId();

        return DB::transaction(function () use ($variantId, $quantity, $type, $warehouseId) {
            $stock = $this->lockStock($variantId, $warehouseId);

            $field = $type === 'deal' ? 'deal_hold' : 'flash_sale_hold';
            $movementType = $type === 'deal' ? 'deal_release' : 'flash_sale_release';

            $before = (int) $stock->{$field};
            $newValue = max(0, $before - $quantity);
            $stock->update([$field => $newValue, 'last_movement_at' => now()]);

            $this->recordMovement([
                'warehouse_id' => $warehouseId,
                'variant_id' => $variantId,
                'movement_type' => $movementType,
                'quantity' => -$quantity,
                'physical_before' => $stock->physical_stock,
                'physical_after' => $stock->physical_stock,
                'reserved_before' => $stock->reserved_stock,
                'reserved_after' => $stock->reserved_stock,
                'available_before' => $this->getStock($variantId, $warehouseId)->availableStock,
                'available_after' => $this->getStock($variantId, $warehouseId)->availableStock,
                'reason' => "Promotion release ({$type})",
            ]);

            $this->clearStockCache($variantId, $warehouseId);

            return ['success' => true, 'message' => 'OK', 'before' => $before, 'after' => $newValue];
        });
    }

    public function deductStockForOrder(int $variantId, int $quantity, string $reason = 'order'): array
    {
        if ($quantity <= 0) {
            return ['success' => false, 'message' => 'Invalid quantity'];
        }

        $warehouseId = $this->getDefaultWarehouseId();

        return DB::transaction(function () use ($variantId, $quantity, $reason, $warehouseId) {
            $dto = $this->getStock($variantId, $warehouseId);
            if ($dto->sellableStock < $quantity) {
                throw new InsufficientStockException("Insufficient sellable stock. Available: {$dto->sellableStock}");
            }

            $stock = $this->lockStock($variantId, $warehouseId);
            $before = $stock->physical_stock;
            $stock->decrement('physical_stock', $quantity);
            $stock->update(['last_movement_at' => now()]);
            $after = $stock->fresh()->physical_stock;

            $this->recordMovement([
                'warehouse_id' => $warehouseId,
                'variant_id' => $variantId,
                'movement_type' => 'sale',
                'quantity' => -$quantity,
                'physical_before' => $before,
                'physical_after' => $after,
                'reserved_before' => $stock->reserved_stock,
                'reserved_after' => $stock->reserved_stock,
                'available_before' => $dto->availableStock,
                'available_after' => $this->getStock($variantId, $warehouseId)->availableStock,
                'reason' => $reason,
            ]);

            $this->clearStockCache($variantId, $warehouseId);
            $this->checkAndCreateAlerts($stock->fresh());

            return ['success' => true, 'message' => 'OK', 'before' => $before, 'after' => $after];
        });
    }

    public function importStock(int $variantId, int $quantity, string $reason = 'manual_import'): array
    {
        if ($quantity <= 0) {
            return ['success' => false, 'message' => 'Invalid quantity'];
        }

        $warehouseId = $this->getDefaultWarehouseId();

        return DB::transaction(function () use ($variantId, $quantity, $reason, $warehouseId) {
            $stock = $this->lockStock($variantId, $warehouseId);
            $before = $stock->physical_stock;
            $stock->increment('physical_stock', $quantity);
            $stock->update(['last_movement_at' => now()]);
            $after = $stock->fresh()->physical_stock;

            $this->recordMovement([
                'warehouse_id' => $warehouseId,
                'variant_id' => $variantId,
                'movement_type' => 'import',
                'quantity' => $quantity,
                'physical_before' => $before,
                'physical_after' => $after,
                'reserved_before' => $stock->reserved_stock,
                'reserved_after' => $stock->reserved_stock,
                'available_before' => max(0, $before - $stock->reserved_stock),
                'available_after' => max(0, $after - $stock->reserved_stock),
                'reason' => $reason,
            ]);

            $this->clearStockCache($variantId, $warehouseId);
            StockAlert::autoResolve($warehouseId, $variantId, $after, (int) $stock->low_stock_threshold);

            return ['success' => true, 'message' => 'OK', 'before' => $before, 'after' => $after];
        });
    }

    public function manualExportStock(int $variantId, int $quantity, string $reason = 'manual_export'): array
    {
        if ($quantity <= 0) {
            return ['success' => false, 'message' => 'Invalid quantity'];
        }

        $warehouseId = $this->getDefaultWarehouseId();

        return DB::transaction(function () use ($variantId, $quantity, $reason, $warehouseId) {
            $dto = $this->getStock($variantId, $warehouseId);
            if ($dto->availableStock < $quantity) {
                throw new InsufficientStockException("Insufficient stock. Available: {$dto->availableStock}");
            }

            $stock = $this->lockStock($variantId, $warehouseId);
            $before = $stock->physical_stock;
            $stock->decrement('physical_stock', $quantity);
            $stock->update(['last_movement_at' => now()]);
            $after = $stock->fresh()->physical_stock;

            $this->recordMovement([
                'warehouse_id' => $warehouseId,
                'variant_id' => $variantId,
                'movement_type' => 'export',
                'quantity' => -$quantity,
                'physical_before' => $before,
                'physical_after' => $after,
                'reserved_before' => $stock->reserved_stock,
                'reserved_after' => $stock->reserved_stock,
                'available_before' => $dto->availableStock,
                'available_after' => $this->getStock($variantId, $warehouseId)->availableStock,
                'reason' => $reason,
            ]);

            $this->clearStockCache($variantId, $warehouseId);
            $this->checkAndCreateAlerts($stock->fresh());

            return ['success' => true, 'message' => 'OK', 'before' => $before, 'after' => $after];
        });
    }

    /**
     * Deduct stock from correct source (Flash Sale/Deal/Available) for order fulfillment.
     *
     * @param  string  $type  'flash_sale', 'deal', or 'available'
     */
    public function deductStockForOrderFulfillment(int $variantId, int $quantity, string $type = 'available', string $reason = 'order_fulfillment'): array
    {
        if ($quantity <= 0) {
            return ['success' => false, 'message' => 'Invalid quantity'];
        }

        $warehouseId = $this->getDefaultWarehouseId();

        return DB::transaction(function () use ($variantId, $quantity, $type, $reason, $warehouseId) {
            $stock = $this->lockStock($variantId, $warehouseId);
            $dto = $this->getStock($variantId, $warehouseId);

            // Check available stock
            if ($dto->sellableStock < $quantity) {
                throw new InsufficientStockException("Insufficient sellable stock. Available: {$dto->sellableStock}");
            }

            $physicalBefore = $stock->physical_stock;
            $flashSaleHoldBefore = $stock->flash_sale_hold;
            $dealHoldBefore = $stock->deal_hold;

            // Always deduct from physical_stock
            $stock->decrement('physical_stock', $quantity);

            // Also reduce hold if from Flash Sale or Deal
            if ($type === 'flash_sale') {
                $newFlashSaleHold = max(0, $flashSaleHoldBefore - $quantity);
                $stock->update(['flash_sale_hold' => $newFlashSaleHold]);
            } elseif ($type === 'deal') {
                $newDealHold = max(0, $dealHoldBefore - $quantity);
                $stock->update(['deal_hold' => $newDealHold]);
            }

            $stock->update(['last_movement_at' => now()]);

            $physicalAfter = $stock->fresh()->physical_stock;
            $flashSaleHoldAfter = $stock->fresh()->flash_sale_hold;
            $dealHoldAfter = $stock->fresh()->deal_hold;

            $this->recordMovement([
                'warehouse_id' => $warehouseId,
                'variant_id' => $variantId,
                'movement_type' => 'order_fulfillment',
                'quantity' => -$quantity,
                'physical_before' => $physicalBefore,
                'physical_after' => $physicalAfter,
                'reserved_before' => $stock->reserved_stock,
                'reserved_after' => $stock->reserved_stock,
                'available_before' => $dto->availableStock,
                'available_after' => $this->getStock($variantId, $warehouseId)->availableStock,
                'reason' => "{$reason} ({$type})",
            ]);

            $this->clearStockCache($variantId, $warehouseId);
            $this->checkAndCreateAlerts($stock->fresh());

            return [
                'success' => true,
                'message' => 'OK',
                'physical_before' => $physicalBefore,
                'physical_after' => $physicalAfter,
                'flash_sale_hold_before' => $flashSaleHoldBefore,
                'flash_sale_hold_after' => $flashSaleHoldAfter,
                'deal_hold_before' => $dealHoldBefore,
                'deal_hold_after' => $dealHoldAfter,
            ];
        });
    }
}
