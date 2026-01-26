<?php

declare(strict_types=1);

namespace App\Http\Resources\FooterBlock;

use Illuminate\Http\Resources\Json\JsonResource;

class FooterBlockResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'tags' => $this->tags ?? [],
            'links' => $this->links ?? [],
            'status' => $this->status,
            'sort' => $this->when(isset($this->sort), $this->sort ?? 0),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
