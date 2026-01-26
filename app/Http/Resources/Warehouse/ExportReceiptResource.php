<?php

declare(strict_types=1);
namespace App\Http\Resources\Warehouse;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Export Receipt Resource for API responses
 * 
 * Formats export receipt data for API output
 */
class ExportReceiptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $items = $this->whenLoaded('items') ?? collect([]);
        $totalValue = $items->sum(function ($item) {
            return ($item->price ?? 0) * ($item->qty ?? 0);
        });
        
        return [
            'id' => $this->id,
            'code' => $this->code,
            'receipt_code' => getExportReceiptCode($this->id, $this->created_at),
            'subject' => $this->subject,
            'content' => $this->content,
            'vat_invoice' => getVatInvoiceFromContent($this->content ?? ''),
            'type' => $this->type,
            'user' => [
                'id' => $this->user?->id ?? null,
                'name' => $this->user?->name ?? null,
            ],
            'items' => ReceiptItemResource::collection($items),
            'total_items' => $items->count(),
            'total_quantity' => $items->sum('qty'),
            'total_value' => $totalValue,
            'total_value_in_words' => convertNumberToWords($totalValue) . ' đồng',
            'qr_code_url' => generateQRCode(url('/admin/export-goods/print/' . $this->id), 120),
            'view_url' => url('/admin/export-goods/print/' . $this->id),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
