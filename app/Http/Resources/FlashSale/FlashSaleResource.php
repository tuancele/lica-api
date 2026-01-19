<?php

namespace App\Http\Resources\FlashSale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Flash Sale Resource for API responses
 * 
 * Formats Flash Sale data with ISO 8601 dates and computed fields
 */
class FlashSaleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        // Convert Unix timestamp to ISO 8601 format
        $startDate = $this->start ? date('c', $this->start) : null;
        $endDate = $this->end ? date('c', $this->end) : null;

        return [
            'id' => $this->id,
            'name' => $this->name ?? "Flash Sale #{$this->id}",
            'start' => $startDate,
            'end' => $endDate,
            'start_timestamp' => $this->start,
            'end_timestamp' => $this->end,
            'status' => (string) $this->status,
            'is_active' => $this->is_active,
            'countdown_seconds' => $this->countdown_seconds,
            'total_products' => $this->total_products ?? $this->products()->count(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
