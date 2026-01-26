<?php

declare(strict_types=1);
namespace App\Http\Resources\Ingredient;

use Illuminate\Http\Resources\Json\JsonResource;

class IngredientResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => (string) $this->status,
            'description' => $this->description,
            'content' => $this->content,
            'glance' => $this->glance,
            'reference' => $this->reference,
            'disclaimer' => $this->disclaimer,
            'shortcode' => $this->shortcode,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'rate' => $this->whenLoaded('rate', function () {
                return $this->rate ? [
                    'id' => $this->rate->id,
                    'name' => $this->rate->name,
                ] : null;
            }),
            'categories' => $this->whenLoaded('categories', function () {
                return $this->categories->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                    ];
                })->values();
            }),
            'benefits' => $this->whenLoaded('benefits', function () {
                return $this->benefits->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                    ];
                })->values();
            }),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
