<?php

declare(strict_types=1);
namespace App\Http\Resources\Compare;

use Illuminate\Http\Resources\Json\JsonResource;

class CompareResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'brand' => $this->when(isset($this->brand), $this->brand),
            'link' => $this->link,
            'store_id' => $this->store_id,
            'status' => $this->status,
            'store' => $this->when(
                $this->relationLoaded('store') && $this->store,
                function () {
                    return [
                        'id' => $this->store->id,
                        'name' => $this->store->name,
                    ];
                }
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

