<?php

declare(strict_types=1);

namespace App\Http\Resources\Product;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Product Resource for API responses.
 *
 * Formats product data for API output
 */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'video' => $this->video,
            'image' => $this->image,
            'gallery' => $this->getGalleryArray(),
            'content' => $this->content,
            'description' => $this->description,
            'cbmp' => $this->cbmp,
            'has_variants' => (int) ($this->has_variants ?? 0),
            'option1_name' => $this->option1_name,
            'ingredient' => $this->ingredient,
            'price_info' => $this->price_info ?? null,
            'status' => $this->status,
            'feature' => $this->feature,
            'best' => $this->best,
            'stock' => 0, // Legacy field - deprecated, use warehouse_stock instead
            'warehouse_stock' => isset($this->warehouse_stock) ? (int) $this->warehouse_stock : 0,
            'is_out_of_stock' => isset($this->is_out_of_stock) ? (bool) $this->is_out_of_stock : (isset($this->warehouse_stock) ? ((int) $this->warehouse_stock <= 0) : true),
            'verified' => $this->verified,
            'sort' => $this->sort,
            'brand_id' => $this->brand_id,
            'origin_id' => $this->origin_id,
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'origin' => new OriginResource($this->whenLoaded('origin')),
            'variants' => VariantResource::collection($this->whenLoaded('variants')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'categories' => $this->getCategoriesArray(),
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get gallery as array.
     */
    private function getGalleryArray(): array
    {
        $gallery = json_decode($this->gallery ?? '[]', true);

        return is_array($gallery) ? $gallery : [];
    }

    /**
     * Get categories as array.
     */
    private function getCategoriesArray(): array
    {
        $catIds = json_decode($this->cat_id ?? '[]', true);

        return is_array($catIds) ? $catIds : [];
    }
}
