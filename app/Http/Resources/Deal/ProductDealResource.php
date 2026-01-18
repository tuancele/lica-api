<?php

namespace App\Http\Resources\Deal;

use App\Http\Resources\Product\VariantResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Product Deal Resource for API responses
 * 
 * Formats ProductDeal data with product and variant information
 */
class ProductDealResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $product = $this->whenLoaded('product');
        $variant = $this->whenLoaded('variant');

        // Get product price from variant or product
        $price = 0;
        if ($variant) {
            $price = (float) $variant->price;
        } elseif ($product) {
            $productVariant = $product->variant($product->id);
            if ($productVariant) {
                $price = (float) $productVariant->price;
            }
        }

        $finalPrice = $price;

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'product' => $this->whenLoaded('product', function() use ($finalPrice) {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'image' => getImage($this->product->image),
                    'has_variants' => (bool) ($this->product->has_variants ?? 0),
                    'price' => $finalPrice,
                    'stock' => (int) ($this->product->stock ?? 0),
                ];
            }),
            'variant' => $this->whenLoaded('variant', function() {
                return $this->variant ? new VariantResource($this->variant) : null;
            }),
            'status' => (string) $this->status,
            'status_text' => $this->status == '1' ? 'Kích hoạt' : 'Ngừng',
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
