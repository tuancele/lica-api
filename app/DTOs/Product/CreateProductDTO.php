<?php

declare(strict_types=1);

namespace App\DTOs\Product;

/**
 * Data Transfer Object for creating a Product.
 *
 * Keep fields aligned with Product model columns used by ProductService.
 */
class CreateProductDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $content = '',
        public string $description = '',
        public ?string $image = null,
        public string $gallery = '[]',
        public ?string $video = null,
        public string $catId = '[]',
        public ?int $brandId = null,
        public ?int $originId = null,
        public int $status = 1,
        public int $type = 0,
        public int $hasVariants = 0,
        public ?string $option1Name = null,
        public string $feature = '0',
        public string $best = '0',
        public string $stock = '1',
        public string $ingredient = '',
        public string $verified = '0',
        public ?string $seoTitle = null,
        public ?string $seoDescription = null,
        public ?string $cbmp = null,
        public ?int $userId = null,
        public float $weight = 0.0,
        public float $length = 0.0,
        public float $width = 0.0,
        public float $height = 0.0
    ) {}

    /**
    * Build DTO from raw request/array data.
    */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            slug: (string) ($data['slug'] ?? ''),
            content: (string) ($data['content'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            image: $data['image'] ?? null,
            gallery: (string) ($data['gallery'] ?? '[]'),
            video: $data['video'] ?? null,
            catId: (string) ($data['cat_id'] ?? '[]'),
            brandId: isset($data['brand_id']) ? (int) $data['brand_id'] : null,
            originId: isset($data['origin_id']) ? (int) $data['origin_id'] : null,
            status: isset($data['status']) ? (int) $data['status'] : 1,
            type: isset($data['type']) ? (int) $data['type'] : 0,
            hasVariants: isset($data['has_variants']) ? (int) $data['has_variants'] : 0,
            option1Name: $data['option1_name'] ?? null,
            feature: (string) ($data['feature'] ?? '0'),
            best: (string) ($data['best'] ?? '0'),
            stock: (string) ($data['stock'] ?? '1'),
            ingredient: (string) ($data['ingredient'] ?? ''),
            verified: (string) ($data['verified'] ?? '0'),
            seoTitle: $data['seo_title'] ?? null,
            seoDescription: $data['seo_description'] ?? null,
            cbmp: $data['cbmp'] ?? null,
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            weight: isset($data['weight']) ? (float) $data['weight'] : 0.0,
            length: isset($data['length']) ? (float) $data['length'] : 0.0,
            width: isset($data['width']) ? (float) $data['width'] : 0.0,
            height: isset($data['height']) ? (float) $data['height'] : 0.0
        );
    }

    /**
     * Convert DTO back to array for repository usage.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'content' => $this->content,
            'description' => $this->description,
            'image' => $this->image,
            'gallery' => $this->gallery,
            'video' => $this->video,
            'cat_id' => $this->catId,
            'brand_id' => $this->brandId,
            'origin_id' => $this->originId,
            'status' => $this->status,
            'type' => $this->type,
            'has_variants' => $this->hasVariants,
            'option1_name' => $this->option1Name,
            'feature' => $this->feature,
            'best' => $this->best,
            'stock' => $this->stock,
            'ingredient' => $this->ingredient,
            'verified' => $this->verified,
            'seo_title' => $this->seoTitle,
            'seo_description' => $this->seoDescription,
            'cbmp' => $this->cbmp,
            'user_id' => $this->userId,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}


