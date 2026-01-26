<?php

declare(strict_types=1);

namespace App\Http\Resources\Pick;

use Illuminate\Http\Resources\Json\JsonResource;

class PickResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'tel' => $this->tel,
            'province_id' => $this->province_id,
            'district_id' => $this->district_id,
            'ward_id' => $this->ward_id,
            'address' => $this->address,
            'status' => $this->status,
            'sort' => $this->when(isset($this->sort), $this->sort ?? 0),
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
