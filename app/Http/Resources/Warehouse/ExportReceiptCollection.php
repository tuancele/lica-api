<?php

declare(strict_types=1);
namespace App\Http\Resources\Warehouse;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Export Receipt Collection Resource
 * 
 * Formats collection of export receipts for API output
 */
class ExportReceiptCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'data' => $this->collection->map(function ($receipt) {
                $items = $receipt->items ?? collect([]);
                $totalValue = $items->sum(function ($item) {
                    return ($item->price ?? 0) * ($item->qty ?? 0);
                });
                
                return [
                    'id' => $receipt->id,
                    'code' => $receipt->code,
                    'receipt_code' => getExportReceiptCode($receipt->id, $receipt->created_at),
                    'subject' => $receipt->subject,
                    'content' => $receipt->content,
                    'vat_invoice' => getVatInvoiceFromContent($receipt->content ?? ''),
                    'type' => $receipt->type,
                    'user' => [
                        'id' => $receipt->user?->id ?? null,
                        'name' => $receipt->user?->name ?? null,
                    ],
                    'total_items' => $items->count(),
                    'total_quantity' => $items->sum('qty'),
                    'total_value' => $totalValue,
                    'created_at' => $receipt->created_at?->toISOString(),
                    'updated_at' => $receipt->updated_at?->toISOString(),
                ];
            }),
        ];
    }
}
