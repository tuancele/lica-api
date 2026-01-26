<?php

declare(strict_types=1);

namespace App\Http\Resources\Order;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * User Order Resource for API responses.
 *
 * Simplified format for user order list (mobile app)
 */
class UserOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'date' => $this->created_at ? $this->formatDate($this->created_at) : null,
            'date_raw' => $this->created_at?->toISOString(),
            'address' => $this->getFullAddress(),
            'total' => (float) $this->total,
            'total_formatted' => $this->formatPrice($this->total),
            'payment_status' => $this->payment,
            'payment_label' => $this->getPaymentLabel($this->payment),
            'ship_status' => $this->ship,
            'ship_label' => $this->getShipLabel($this->ship),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel($this->status),
        ];
    }

    /**
     * Get full address string.
     */
    private function getFullAddress(): string
    {
        $parts = [];

        if ($this->address) {
            $parts[] = $this->address;
        }

        if ($this->whenLoaded('ward') && $this->ward && isset($this->ward->name)) {
            $parts[] = $this->ward->name;
        }

        if ($this->whenLoaded('district') && $this->district && isset($this->district->name)) {
            $parts[] = $this->district->name;
        }

        if ($this->whenLoaded('province') && $this->province && isset($this->province->name)) {
            $parts[] = $this->province->name;
        }

        // Fallback to text fields if relationships not loaded
        if (empty($parts) && $this->address) {
            $parts[] = $this->address;
            if ($this->ward) {
                $parts[] = $this->ward;
            }
            if ($this->district) {
                $parts[] = $this->district;
            }
            if ($this->province) {
                $parts[] = $this->province;
            }
        }

        return ! empty($parts) ? implode(', ', $parts) : '';
    }

    /**
     * Format date.
     *
     * @param  \Carbon\Carbon  $date
     */
    private function formatDate($date): string
    {
        return $date->format('d-m-Y');
    }

    /**
     * Format price.
     */
    private function formatPrice(float $price): string
    {
        return number_format($price, 0, ',', '.').'₫';
    }

    /**
     * Get status label.
     */
    private function getStatusLabel(?string $status): string
    {
        $labels = [
            '0' => 'Chờ xử lý',
            '1' => 'Đã xác nhận',
            '2' => 'Đã giao hàng',
            '3' => 'Hoàn thành',
            '4' => 'Đã hủy',
        ];

        return $labels[$status] ?? 'Không xác định';
    }

    /**
     * Get payment label.
     */
    private function getPaymentLabel(?string $payment): string
    {
        $labels = [
            '0' => 'Chưa thanh toán',
            '1' => 'Đã thanh toán',
            '2' => 'Hoàn trả',
        ];

        return $labels[$payment] ?? 'Không xác định';
    }

    /**
     * Get ship label.
     */
    private function getShipLabel(?string $ship): string
    {
        $labels = [
            '0' => 'Chưa giao hàng',
            '1' => 'Đã giao hàng',
            '2' => 'Đã nhận',
            '3' => 'Hoàn trả',
            '4' => 'Đã hủy',
        ];

        return $labels[$ship] ?? 'Không xác định';
    }
}
