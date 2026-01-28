<?php

declare(strict_types=1);

namespace App\DTOs\Product;

use Illuminate\Support\Str;

/**
 * Data Transfer Object for creating a Product (and its default variant).
 */
class CreateProductDTO
{
    public function __construct(
        public string $name,
        public ?string $slug = null,
        public ?string $description = null,
        public ?float $price = null,
        public int $categoryId,
        public ?int $brandId = null,
        public int $status = 1
    ) {}

    /**
    * Build DTO from raw request/array data.
    */
    public static function fromArray(array $data): self
    {
        $name = (string) ($data['name'] ?? '');
        $slug = $data['slug'] ?? null;

        return new self(
            name: $name,
            slug: $slug !== null && $slug !== '' ? $slug : Str::slug($name),
            description: $data['description'] ?? null,
            price: isset($data['price']) ? (float) $data['price'] : null,
            categoryId: (int) ($data['category_id'] ?? 0),
            brandId: isset($data['brand_id']) ? (int) $data['brand_id'] : null,
            status: isset($data['status']) ? (int) $data['status'] : 1
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
            'description' => $this->description,
            'price' => $this->price,
            'cat_id' => (string) $this->categoryId,
            'brand_id' => $this->brandId,
            'status' => $this->status,
        ];
    }
}


