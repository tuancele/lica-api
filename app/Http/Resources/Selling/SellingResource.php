<?php

declare(strict_types=1);
namespace App\Http\Resources\Selling;

use Illuminate\Http\Resources\Json\JsonResource;

class SellingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'product_id' => $this->product_id,
            'image' => getImage($this->image),
            'status' => $this->status,
            'product' => $this->when(
                $this->relationLoaded('product') && $this->product,
                function () {
                    return [
                        'id' => $this->product->id,
                        'name' => $this->product->name ?? null,
                        'slug' => $this->product->slug ?? null,
                    ];
                }
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

