<?php

declare(strict_types=1);

namespace App\Http\Resources\Member;

use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'member_id' => $this->member_id,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'address' => $this->address,
            'province_id' => $this->province_id,
            'district_id' => $this->district_id,
            'ward_id' => $this->ward_id,
            'is_default' => $this->when(isset($this->is_default), $this->is_default ?? 0),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
