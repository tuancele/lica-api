<?php

namespace App\Http\Resources\Deal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Deal Resource for API responses
 * 
 * Formats Deal data with ISO 8601 dates and computed fields
 */
class DealResource extends JsonResource
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

        $now = strtotime(date('Y-m-d H:i:s'));
        $isActive = $this->status == '1' 
            && $this->start <= $now 
            && $this->end >= $now;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'start' => $startDate,
            'end' => $endDate,
            'start_timestamp' => $this->start,
            'end_timestamp' => $this->end,
            'status' => (string) $this->status,
            'status_text' => $this->status == '1' ? 'Kích hoạt' : 'Ngừng',
            'limited' => (int) $this->limited,
            'is_active' => $isActive,
            'created_by' => $this->whenLoaded('user', function() {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name ?? 'N/A',
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
