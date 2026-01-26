<?php

declare(strict_types=1);

namespace App\Services\Inventory\DTOs;

class AdjustStockDTO
{
    /**
     * @param  int  $warehouseId  ID kho
     * @param  array  $items  Danh sách điều chỉnh [{variant_id, new_quantity, reason?}]
     * @param  int  $createdBy  ID người tạo
     * @param  string|null  $subject  Tiêu đề (vd: Kiểm kê tháng 1/2025)
     * @param  string|null  $content  Ghi chú
     * @param  array|null  $metadata  Dữ liệu bổ sung
     */
    public function __construct(
        public int $warehouseId,
        public array $items,
        public int $createdBy,
        public ?string $subject = null,
        public ?string $content = null,
        public ?array $metadata = null,
    ) {
        $this->validateItems();
    }

    /**
     * Validate items.
     */
    private function validateItems(): void
    {
        if (empty($this->items)) {
            throw new \InvalidArgumentException('Items array cannot be empty');
        }

        foreach ($this->items as $index => $item) {
            if (! isset($item['variant_id'])) {
                throw new \InvalidArgumentException("Item at index {$index} is missing variant_id");
            }
            if (! isset($item['new_quantity']) || $item['new_quantity'] < 0) {
                throw new \InvalidArgumentException("Item at index {$index} has invalid new_quantity");
            }
        }
    }

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            warehouseId: $data['warehouse_id'] ?? config('inventory.default_warehouse_id', 1),
            items: $data['items'],
            createdBy: $data['created_by'] ?? auth()->id(),
            subject: $data['subject'] ?? 'Điều chỉnh tồn kho',
            content: $data['content'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * Get item count.
     */
    public function getItemCount(): int
    {
        return count($this->items);
    }
}
