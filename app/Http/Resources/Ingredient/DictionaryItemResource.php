<?php

declare(strict_types=1);

namespace App\Http\Resources\Ingredient;

use Illuminate\Http\Resources\Json\JsonResource;

class DictionaryItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => (string) $this->status,
            'sort' => $this->sort ?? 0,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
