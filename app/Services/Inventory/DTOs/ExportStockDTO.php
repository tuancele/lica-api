<?php

declare(strict_types=1);

namespace App\Services\Inventory\DTOs;

class ExportStockDTO
{
    /**
     * @param  string  $code  Mã đơn hàng
     * @param  string  $subject  Tiêu đề / Nội dung xuất
     * @param  int  $warehouseId  ID kho xuất
     * @param  array  $items  Danh sách sản phẩm [{variant_id, quantity, unit_price}]
     * @param  int  $createdBy  ID người tạo
     * @param  string|null  $content  Ghi chú
     * @param  string|null  $vatInvoice  Số hóa đơn VAT
     * @param  string|null  $referenceType  Loại tham chiếu (order, manual, etc.)
     * @param  int|null  $referenceId  ID tham chiếu
     * @param  string|null  $referenceCode  Mã tham chiếu
     * @param  int|null  $customerId  ID khách hàng
     * @param  string|null  $customerName  Tên khách hàng
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
        public ?string $referenceType = null,
        public ?int $referenceId = null,
        public ?string $referenceCode = null,
        public ?int $customerId = null,
        public ?string $customerName = null,
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
            referenceType: $data['reference_type'] ?? null,
            referenceId: $data['reference_id'] ?? null,
            referenceCode: $data['reference_code'] ?? null,
            customerId: $data['customer_id'] ?? null,
            customerName: $data['customer_name'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * Create from order.
     */
    public static function fromOrder($order, int $warehouseId, int $createdBy): self
    {
        $items = [];

        foreach ($order->details as $detail) {
            $items[] = [
                'variant_id' => $detail->variant_id,
                'quantity' => $detail->qty,
                'unit_price' => $detail->price,
            ];
        }

        return new self(
            code: 'ORD-'.$order->code,
            subject: 'Xuất hàng cho đơn #'.$order->code,
            warehouseId: $warehouseId,
            items: $items,
            createdBy: $createdBy,
            referenceType: 'order',
            referenceId: $order->id,
            referenceCode: $order->code,
            customerId: $order->user_id,
            customerName: $order->name,
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
