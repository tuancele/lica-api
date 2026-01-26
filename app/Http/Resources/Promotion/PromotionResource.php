<?php

declare(strict_types=1);

namespace App\Http\Resources\Promotion;

use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'value' => $this->value,
            'unit' => $this->unit,
            'number' => $this->number,
            'start' => $this->start,
            'end' => $this->end,
            'status' => $this->status,
            'endow' => $this->when(isset($this->endow), $this->endow),
            'order_sale' => $this->when(isset($this->order_sale), $this->order_sale),
            'payment' => $this->when(isset($this->payment), $this->payment),
            'content' => $this->when(isset($this->content), $this->content),
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
