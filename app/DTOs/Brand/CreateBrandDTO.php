<?php

declare(strict_types=1);

namespace App\DTOs\Brand;

/**
 * DTO for creating a Brand.
 */
class CreateBrandDTO
{
    public string $name;
    public ?string $slug;
    public ?string $description;
    public int $status;
    public int $sort;

    public function __construct(
        string $name,
        ?string $slug = null,
        ?string $description = null,
        int $status = 1,
        int $sort = 0
    ) {
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->status = $status;
        $this->sort = $sort;
    }

    /**
     * Build DTO from validated request data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            slug: $data['slug'] ?? null,
            description: $data['description'] ?? null,
            status: (int) ($data['status'] ?? 1),
            sort: (int) ($data['sort'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status,
            'sort' => $this->sort,
        ];
    }
}


