<?php

declare(strict_types=1);

namespace App\Http\Resources\Rate;

use Illuminate\Http\Resources\Json\JsonResource;

class RateResource extends JsonResource
{
    public function toArray($request): array
    {
        $images = [];
        if (! empty($this->images)) {
            $imageData = is_string($this->images) ? json_decode($this->images, true) : $this->images;
            if (is_array($imageData)) {
                $images = array_map(function ($url) {
                    return getImage($url);
                }, $imageData);
            }
        }

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'name' => $this->name,
            'email' => $this->when(isset($this->email), $this->email),
            'phone' => $this->when(isset($this->phone), $this->phone),
            'rate' => $this->rate,
            'content' => $this->content,
            'images' => $images,
            'status' => $this->status,
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
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
