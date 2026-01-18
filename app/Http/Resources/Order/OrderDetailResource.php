<?php

namespace App\Http\Resources\Order;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Order Detail Resource for API responses
 * 
 * Formats order detail data including items list
 */
class OrderDetailResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'province' => $this->when($this->whenLoaded('province'), function() {
                return [
                    'id' => $this->province->provinceid,
                    'name' => $this->province->name,
                ];
            }),
            'district' => $this->when($this->whenLoaded('district'), function() {
                return [
                    'id' => $this->district->districtid,
                    'name' => $this->district->name,
                ];
            }),
            'ward' => $this->when($this->whenLoaded('ward'), function() {
                return [
                    'id' => $this->ward->wardid,
                    'name' => $this->ward->name,
                ];
            }),
            'remark' => $this->remark,
            'content' => $this->content,
            'total' => (float)$this->total,
            'sale' => (float)$this->sale,
            'fee_ship' => (float)$this->fee_ship,
            'final_total' => (float)($this->total + $this->fee_ship - $this->sale),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel($this->status),
            'payment' => $this->payment,
            'payment_label' => $this->getPaymentLabel($this->payment),
            'ship' => $this->ship,
            'ship_label' => $this->getShipLabel($this->ship),
            'promotion' => $this->when($this->whenLoaded('promotion'), function() {
                return [
                    'id' => $this->promotion->id,
                    'code' => $this->promotion->code,
                    'name' => $this->promotion->name ?? '',
                ];
            }),
            'member' => $this->when($this->whenLoaded('member'), function() {
                return [
                    'id' => $this->member->id,
                    'name' => $this->member->name ?? '',
                    'email' => $this->member->email ?? '',
                ];
            }),
            'items' => OrderItemResource::collection($this->whenLoaded('detail')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get status label
     * 
     * @param string|null $status
     * @return string
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
     * Get payment label
     * 
     * @param string|null $payment
     * @return string
     */
    private function getPaymentLabel(?string $payment): string
    {
        $labels = [
            '0' => 'Chưa thanh toán',
            '1' => 'Đã thanh toán',
            '2' => 'Bị hoàn trả',
        ];
        
        return $labels[$payment] ?? 'Không xác định';
    }

    /**
     * Get ship label
     * 
     * @param string|null $ship
     * @return string
     */
    private function getShipLabel(?string $ship): string
    {
        $labels = [
            '0' => 'Chưa chuyển',
            '1' => 'Đã chuyển',
            '2' => 'Đã nhận',
            '3' => 'Bị hoàn trả',
            '4' => 'Đã hủy',
        ];
        
        return $labels[$ship] ?? 'Không xác định';
    }
}
