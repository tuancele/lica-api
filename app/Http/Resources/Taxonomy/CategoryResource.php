<?php

declare(strict_types=1);

namespace App\Http\Resources\Taxonomy;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        $rawImage = (string) ($this->image ?? '');
        $rawImage = trim($rawImage);

        // Normalize legacy/broken stored values:
        // - "/https://cdn..." should become "https://cdn..."
        // - "https://lica.test/https://cdn..." (or repeated domains) -> take the last valid absolute URL
        if (str_starts_with($rawImage, '/http://') || str_starts_with($rawImage, '/https://')) {
            $rawImage = ltrim($rawImage, '/');
        }

        $imageUrl = '';
        if ($rawImage !== '') {
            if (preg_match_all('#https?://[^\s"\']+#', $rawImage, $matches) && ! empty($matches[0])) {
                // Use the last absolute URL found
                $imageUrl = (string) end($matches[0]);
            } elseif (str_starts_with($rawImage, 'http://') || str_starts_with($rawImage, 'https://')) {
                $imageUrl = $rawImage;
            } else {
                // Relative path -> resolve through helper (R2/CDN)
                $imageUrl = getImage($rawImage);
            }
        }

        return [
            'id' => (int) $this->id,
            'name' => (string) ($this->name ?? ''),
            'slug' => (string) ($this->slug ?? ''),
            'image' => (string) $imageUrl,
            'status' => (int) ($this->status ?? 0),
            'feature' => (int) ($this->feature ?? 0),
            'is_home' => (int) ($this->is_home ?? 0),
            'tracking' => (string) ($this->tracking ?? ''),
            'parent_id' => (int) ($this->cat_id ?? 0),
            'sort' => (int) ($this->sort ?? 0),
            'seo_title' => (string) ($this->seo_title ?? ''),
            'seo_description' => (string) ($this->seo_description ?? ''),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
