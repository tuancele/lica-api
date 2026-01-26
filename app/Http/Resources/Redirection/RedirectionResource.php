<?php

namespace App\Http\Resources\Redirection;

use Illuminate\Http\Resources\Json\JsonResource;

class RedirectionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'link_from' => $this->link_from,
            'link_to' => $this->link_to,
            'type' => $this->type,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

