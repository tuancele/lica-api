<?php

declare(strict_types=1);
namespace App\Http\Resources\Role;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'permissions' => $this->when(
                $this->relationLoaded('permissions'),
                function () {
                    return $this->permissions->pluck('id')->toArray();
                }
            ),
            'permissions_count' => $this->when(
                $this->relationLoaded('permissions'),
                function () {
                    return $this->permissions ? $this->permissions->count() : 0;
                }
            ),
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

