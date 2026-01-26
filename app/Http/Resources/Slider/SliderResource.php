<?php

declare(strict_types=1);
namespace App\Http\Resources\Slider;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Slider Resource for API responses
 * 
 * Formats slider data for API output with full information
 */
class SliderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'link' => $this->when(isset($this->link), $this->link),
            'image' => getImage($this->image),
            'display' => $this->when(isset($this->display), $this->display),
            'status' => $this->status,
            'sort' => $this->when(isset($this->sort), $this->sort ?? 0),
            'user' => $this->when(
                $this->relationLoaded('user') && $this->user,
                function () {
                    return [
                        'id' => $this->user->id,
                        'name' => $this->user->name,
                    ];
                }
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
