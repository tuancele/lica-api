<?php

declare(strict_types=1);

namespace App\Http\Resources\Post;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => getImage($this->image),
            'description' => $this->when(isset($this->description), $this->description),
            'content' => $this->when(isset($this->content), $this->content),
            'cat_id' => $this->cat_id ?? 0,
            'status' => $this->status,
            'seo_title' => $this->when(isset($this->seo_title), $this->seo_title),
            'seo_description' => $this->when(isset($this->seo_description), $this->seo_description),
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
