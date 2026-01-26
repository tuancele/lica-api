<?php

declare(strict_types=1);

namespace App\Http\Resources\Warehouse;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Inventory Resource for API responses.
 *
 * Formats inventory data for API output
 */
class InventoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        // Ưu tiên dùng các alias val để tránh đè bởi logic Model Attribute
        $physicalStock = (int) ($this->physical_stock ?? 0);
        $flashStock = (int) ($this->flash_sale_stock_val ?? $this->flash_sale_stock ?? 0);
        $dealStock = (int) ($this->deal_stock_val ?? $this->deal_stock ?? 0);
        $availableStock = (int) ($this->available_stock_val ?? ($physicalStock - $flashStock - $dealStock));

        return [
            'variant_id' => $this->variant_id,
            'variant_sku' => $this->variant_sku ?? null,
            'variant_option' => $this->variant_option ?? 'Mặc định',
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'product_image' => $this->product_image ?? null,
            'physical_stock' => $physicalStock,
            'flash_sale_stock' => $flashStock,
            'deal_stock' => $dealStock,
            'available_stock' => $availableStock >= 0 ? $availableStock : 0,
        ];
    }
}
