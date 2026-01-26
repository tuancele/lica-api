<?php

declare(strict_types=1);

namespace App\Http\Resources\Product;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Product Collection Resource.
 *
 * Formats collection of products for API output
 */
class ProductCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->resource->total() ?? $this->collection->count(),
                'per_page' => $this->resource->perPage() ?? null,
                'current_page' => $this->resource->currentPage() ?? 1,
                'last_page' => $this->resource->lastPage() ?? null,
            ],
        ];
    }
}
