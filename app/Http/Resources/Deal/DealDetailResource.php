<?php

namespace App\Http\Resources\Deal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Deal Detail Resource for Admin API
 * 
 * Extends DealResource with products and sale_products list
 */
class DealDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        // Get base DealResource data
        $baseData = (new DealResource($this->resource))->toArray($request);

        // Add products list
        $baseData['products'] = ProductDealResource::collection(
            $this->whenLoaded('products')
        );

        // Add sale_products list
        $baseData['sale_products'] = SaleDealResource::collection(
            $this->whenLoaded('sales')
        );

        return $baseData;
    }
}
