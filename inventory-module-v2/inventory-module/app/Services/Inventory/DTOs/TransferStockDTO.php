<?php

namespace App\Services\Inventory\DTOs;

class TransferStockDTO
{
    /**
     * @param  int  $fromWarehouseId  Kho xuất
     * @param  int  $toWarehouseId  Kho nhập
     * @param  array  $items  Danh sách sản phẩm [{variant_id, quantity}]
     * @param  int  $createdBy  ID người tạo
     * @param  string|null  $subject  Tiêu đề
     * @param  string|null  $content  Ghi chú
     * @param  array|null  $metadata  Dữ liệu bổ sung
     */
    public function __construct(
        public int $fromWarehouseId,
        public int $toWarehouseId,
        public array $items,
        public int $createdBy,
        public ?string $subject = null,
        public ?string $content = null,
        public ?array $metadata = null,
    ) {
        $this->validate();
    }

    /**
     * Validate.
     */
    private function validate(): void
    {
        if ($this->fromWarehouseId === $this->toWarehouseId) {
            throw new \InvalidArgumentException('Source and destination warehouse must be different');
        }

        if (empty($this->items)) {
            throw new \InvalidArgumentException('Items array cannot be empty');
        }

        foreach ($this->items as $index => $item) {
            if (! isset($item['variant_id'])) {
                throw new \InvalidArgumentException("Item at index {$index} is missing variant_id");
            }
            if (! isset($item['quantity']) || $item['quantity'] <= 0) {
                throw new \InvalidArgumentException("Item at index {$index} has invalid quantity");
            }
        }
    }

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            fromWarehouseId: $data['from_warehouse_id'],
            toWarehouseId: $data['to_warehouse_id'],
            items: $data['items'],
            createdBy: $data['created_by'] ?? auth()->id(),
            subject: $data['subject'] ?? null,
            content: $data['content'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * Get total quantity.
     */
    public function getTotalQuantity(): int
    {
        return array_sum(array_column($this->items, 'quantity'));
    }

    /**
     * Get item count.
     */
    public function getItemCount(): int
    {
        return count($this->items);
    }
}
