<?php

declare(strict_types=1);
namespace App\Http\Resources\Brand;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Brand Resource for API responses
 * 
 * Formats brand data for API output with full information
 */
class BrandResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $gallery = [];
        if (!empty($this->gallery)) {
            $galleryData = is_string($this->gallery) ? json_decode($this->gallery, true) : $this->gallery;
            if (is_array($galleryData)) {
                $gallery = array_map(function($url) {
                    return getImage($url);
                }, $galleryData);
            }
        }
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'content' => $this->when(isset($this->content), $this->content),
            'image' => getImage($this->image),
            'banner' => $this->when(isset($this->banner), getImage($this->banner)),
            'logo' => $this->when(isset($this->logo), getImage($this->logo)),
            'gallery' => $gallery,
            'seo_title' => $this->when(isset($this->seo_title), $this->seo_title),
            'seo_description' => $this->when(isset($this->seo_description), $this->seo_description),
            'status' => $this->status,
            'sort' => $this->when(isset($this->sort), $this->sort ?? 0),
            'total_products' => $this->when(
                $this->relationLoaded('product'),
                function () {
                    return $this->product ? $this->product->count() : 0;
                }
            ),
            'user' => $this->when(
                $this->relationLoaded('user') && $this->user,
                function () {
                    return [
                        'id' => $this->user->id,
                        'name' => $this->user->name,
                    ];
                }
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
