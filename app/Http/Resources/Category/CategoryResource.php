<?php

declare(strict_types=1);
namespace App\Http\Resources\Category;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Category Resource for API responses
 * 
 * Formats category data for API output with tree structure support
 */
class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => getImage($this->image),
            'description' => $this->when(isset($this->description), $this->description),
            'content' => $this->when(isset($this->content), $this->content),
            'status' => $this->status,
            'feature' => $this->when(isset($this->feature), $this->feature ?? '0'),
            'cat_id' => $this->cat_id ?? 0,
            'parent_id' => $this->cat_id ?? 0,
            'seo_title' => $this->when(isset($this->seo_title), $this->seo_title),
            'seo_description' => $this->when(isset($this->seo_description), $this->seo_description),
            'sort' => $this->when(isset($this->sort), $this->sort ?? 0),
            'children' => $this->when(
                $this->relationLoaded('children'),
                function () {
                    return CategoryResource::collection($this->children);
                }
            ),
            'children_count' => $this->when(
                $this->relationLoaded('children'),
                function () {
                    return $this->children ? $this->children->count() : 0;
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

