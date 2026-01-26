<?php

declare(strict_types=1);

namespace App\Http\Resources\Marketing;

use Illuminate\Http\Resources\Json\JsonResource;

class MarketingCampaignResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'status' => $this->status,
            'products_count' => $this->when(
                $this->relationLoaded('products'),
                function () {
                    return $this->products ? $this->products->count() : 0;
                }
            ),
            'products' => $this->when(
                $this->relationLoaded('products'),
                function () {
                    return MarketingCampaignProductResource::collection($this->products);
                }
            ),
            'user' => $this->when(
                $this->relationLoaded('user') && $this->user,
                function () {
                    return ['id' => $this->user->id, 'name' => $this->user->name];
                }
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
