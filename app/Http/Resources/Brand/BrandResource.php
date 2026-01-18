<?php

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
        // Parse gallery JSON to array
        $gallery = json_decode($this->gallery ?? '[]', true);
        $galleryArray = is_array($gallery) ? $gallery : [];
        
        // Format gallery images using getImage helper
        $formattedGallery = array_map(function($image) {
            return getImage($image);
        }, $galleryArray);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => getImage($this->image),
            'banner' => $this->when(isset($this->banner), getImage($this->banner)),
            'logo' => $this->when(isset($this->logo), getImage($this->logo)),
            'content' => $this->when(isset($this->content), $this->content),
            'gallery' => $this->when(!empty($formattedGallery), $formattedGallery),
            'status' => $this->status,
            'total_products' => $this->when(isset($this->total_products), $this->total_products),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
