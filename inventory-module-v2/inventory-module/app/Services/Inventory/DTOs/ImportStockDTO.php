<?php

namespace App\Services\Inventory\DTOs;

class ImportStockDTO
{
    /**
     * @param  string  $code  Mã đơn hàng / PO
     * @param  string  $subject  Tiêu đề / Nội dung
     * @param  int  $warehouseId  ID kho nhập
     * @param  array  $items  Danh sách sản phẩm [{variant_id, quantity, unit_price, batch_number?, expiry_date?, notes?}]
     * @param  int  $createdBy  ID người tạo
     * @param  string|null  $content  Ghi chú
     * @param  string|null  $vatInvoice  Số hóa đơn VAT
     * @param  int|null  $supplierId  ID nhà cung cấp
     * @param  string|null  $supplierName  Tên nhà cung cấp
     * @param  array|null  $metadata  Dữ liệu bổ sung
     */
    public function __construct(
        public string $code,
        public string $subject,
        public int $warehouseId,
        public array $items,
        public int $createdBy,
        public ?string $content = null,
        public ?string $vatInvoice = null,
        public ?int $supplierId = null,
        public ?string $supplierName = null,
        public ?array $metadata = null,
    ) {
        $this->validateItems();
    }

    /**
     * Validate items array.
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
            if (! isset($item['quantity']) || $item['quantity'] <= 0) {
                throw new \InvalidArgumentException("Item at index {$index} has invalid quantity");
            }
        }
    }

    /**
     * Create from request array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            subject: $data['subject'],
            warehouseId: $data['warehouse_id'] ?? config('inventory.default_warehouse_id', 1),
            items: $data['items'],
            createdBy: $data['created_by'] ?? auth()->id(),
            content: $data['content'] ?? null,
            vatInvoice: $data['vat_invoice'] ?? null,
            supplierId: $data['supplier_id'] ?? null,
            supplierName: $data['supplier_name'] ?? null,
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
     * Get total value.
     */
    public function getTotalValue(): float
    {
        return array_reduce($this->items, function ($carry, $item) {
            return $carry + (($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0));
        }, 0);
    }

    /**
     * Get item count.
     */
    public function getItemCount(): int
    {
        return count($this->items);
    }
}
