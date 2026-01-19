<?php

namespace App\Http\Resources\Warehouse;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Inventory Resource for API responses
 * 
 * Formats inventory data for API output
 */
class InventoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'variant_id' => $this->variant_id,
            'variant_sku' => $this->variant_sku ?? null,
            'variant_option' => $this->variant_option ?? 'Mặc định',
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'product_image' => $this->product_image ?? null,
            'import_total' => (int) ($this->import_total ?? 0),
            'export_total' => (int) ($this->export_total ?? 0),
            'current_stock' => max(0, (int) ($this->import_total ?? 0) - (int) ($this->export_total ?? 0)),
            'last_import_date' => $this->last_import_date?->toISOString(),
            'last_export_date' => $this->last_export_date?->toISOString(),
        ];
    }
}
