<?php

declare(strict_types=1);

namespace App\DTOs\Category;

/**
 * DTO for creating a Category (post type = category).
 */
class CreateCategoryDTO
{
    public string $name;
    public ?string $slug;
    public ?string $description;
    public int $status;
    public int $sort;
    public ?int $parentId;

    public function __construct(
        string $name,
        ?string $slug = null,
        ?string $description = null,
        int $status = 1,
        int $sort = 0,
        ?int $parentId = null
    ) {
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->status = $status;
        $this->sort = $sort;
        $this->parentId = $parentId;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            slug: $data['slug'] ?? null,
            description: $data['description'] ?? null,
            status: (int) ($data['status'] ?? 1),
            sort: (int) ($data['sort'] ?? 0),
            parentId: isset($data['cat_id']) ? (int) $data['cat_id'] : null,
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
            'cat_id' => $this->parentId,
        ];
    }
}


