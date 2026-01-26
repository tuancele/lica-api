<?php

declare(strict_types=1);
namespace App\Http\Resources\Marketing;

use Illuminate\Http\Resources\Json\JsonResource;

class MarketingCampaignProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->campaign_id,
            'product_id' => $this->product_id,
            'price' => $this->price,
            'limit' => $this->limit,
            'product' => $this->when(
                $this->relationLoaded('product') && $this->product,
                function () {
                    return [
                        'id' => $this->product->id,
                        'name' => $this->product->name,
                        'slug' => $this->product->slug,
                        'image' => getImage($this->product->image),
                    ];
                }
            ),
        ];
    }
}

