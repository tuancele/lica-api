<?php

declare(strict_types=1);

namespace App\Http\Resources\FlashSale;

use App\Http\Resources\Product\VariantResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Product Sale Resource for API responses.
 *
 * Formats ProductSale data with variant information
 */
class ProductSaleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $variant = $this->whenLoaded('variant');
        $originalPrice = $variant ? $variant->price : ($this->product->variant($this->product_id)->price ?? 0);

        return [
            'id' => $this->id,
            'flashsale_id' => $this->flashsale_id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'price_sale' => (float) $this->price_sale,
            'number' => (int) $this->number,
            'buy' => (int) $this->buy,
            'remaining' => $this->remaining,
            'is_available' => $this->is_available,
            'original_price' => (float) $originalPrice,
            'discount_percent' => $this->discount_percent,
            'variant' => new VariantResource($this->whenLoaded('variant')),
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'slug' => $this->product->slug,
                    'image' => getImage($this->product->image),
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
