<?php

declare(strict_types=1);

namespace App\Http\Resources\Deal;

use App\Http\Resources\Product\VariantResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Sale Deal Resource for API responses.
 *
 * Formats SaleDeal data with product, variant, and savings calculation
 */
class SaleDealResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $product = $this->whenLoaded('product');
        $variant = $this->whenLoaded('variant');

        // Get original price from variant or product
        $originalPrice = 0;
        if ($variant) {
            $originalPrice = (float) $variant->price;
        } elseif ($product) {
            $productVariant = $product->variant($product->id);
            if ($productVariant) {
                $originalPrice = (float) $productVariant->price;
            }
        }

        // Calculate savings amount
        $dealPrice = (float) $this->price;
        $qty = (int) $this->qty;
        $savingsAmount = ($originalPrice - $dealPrice) * $qty;

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'product' => $this->whenLoaded('product', function () use ($originalPrice) {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'image' => getImage($this->product->image),
                    'has_variants' => (bool) ($this->product->has_variants ?? 0),
                    'price' => $originalPrice,
                    'stock' => (int) ($this->product->stock ?? 0),
                ];
            }),
            'variant' => $this->whenLoaded('variant', function () {
                return $this->variant ? new VariantResource($this->variant) : null;
            }),
            'deal_price' => $dealPrice,
            'original_price' => $originalPrice,
            'savings_amount' => $savingsAmount,
            'qty' => $qty,
            'status' => (string) $this->status,
            'status_text' => $this->status == '1' ? 'Kích hoạt' : 'Ngừng',
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
