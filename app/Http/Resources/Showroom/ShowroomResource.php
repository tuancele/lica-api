<?php

declare(strict_types=1);

namespace App\Http\Resources\Showroom;

use Illuminate\Http\Resources\Json\JsonResource;

class ShowroomResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => getImage($this->image),
            'address' => $this->when(isset($this->address), $this->address),
            'phone' => $this->when(isset($this->phone), $this->phone),
            'cat_id' => $this->when(isset($this->cat_id), $this->cat_id),
            'status' => $this->status,
            'sort' => $this->when(isset($this->sort), $this->sort ?? 0),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
