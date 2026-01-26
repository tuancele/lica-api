<?php

declare(strict_types=1);
namespace App\Http\Resources\Warehouse;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Receipt Item Resource for API responses
 * 
 * Formats receipt item data for API output
 */
class ReceiptItemResource extends JsonResource
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
            'id' => $this->id,
            'variant_id' => $this->variant_id,
            'variant_sku' => $this->variant?->sku ?? null,
            'variant_option' => $this->variant?->option1_value ?? 'Mặc định',
            'product_id' => $this->variant?->product_id ?? null,
            'product_name' => $this->variant?->product?->name ?? null,
            'price' => (float) ($this->price ?? 0),
            'quantity' => (int) ($this->qty ?? 0),
            'subtotal' => (float) (($this->price ?? 0) * ($this->qty ?? 0)),
        ];
    }
}
