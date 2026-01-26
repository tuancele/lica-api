<?php

declare(strict_types=1);

namespace App\Http\Resources\FlashSale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Flash Sale Detail Resource for Admin API.
 *
 * Extends FlashSaleResource with products list
 */
class FlashSaleDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // Get base FlashSaleResource data
        $baseData = (new FlashSaleResource($this->resource))->toArray($request);

        // Add products list
        $baseData['products'] = ProductSaleResource::collection(
            $this->whenLoaded('products')
        );

        return $baseData;
    }
}
