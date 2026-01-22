<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Variant Resource for API responses
 */
class VariantResource extends JsonResource
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
            'sku' => $this->sku,
            'product_id' => $this->product_id,
            'option1_value' => $this->option1_value,
            'image' => $this->image,
            'size_id' => $this->size_id,
            'color_id' => $this->color_id,
            'weight' => (float) $this->weight,
            'price' => (float) $this->price,
            'stock' => 0, // Legacy field - deprecated, use warehouse_stock instead
            'warehouse_stock' => isset($this->warehouse_stock) ? (int) $this->warehouse_stock : 0,
            'is_out_of_stock' => isset($this->is_out_of_stock) ? (bool) $this->is_out_of_stock : (isset($this->warehouse_stock) ? ((int) $this->warehouse_stock <= 0) : true),
            'position' => (int) $this->position,
            'color' => new ColorResource($this->whenLoaded('color')),
            'size' => new SizeResource($this->whenLoaded('size')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
