<?php

namespace App\Http\Resources\Member;

use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => trim($this->first_name . ' ' . $this->last_name),
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'addresses' => $this->when(
                $this->relationLoaded('addresses'),
                function () {
                    return AddressResource::collection($this->addresses);
                }
            ),
            'addresses_count' => $this->when(
                $this->relationLoaded('addresses'),
                function () {
                    return $this->addresses ? $this->addresses->count() : 0;
                }
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

