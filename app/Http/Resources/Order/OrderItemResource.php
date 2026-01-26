<?php

declare(strict_types=1);
namespace App\Http\Resources\Order;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Order Item Resource for API responses
 * 
 * Formats order detail (item) data for API output
 */
class OrderItemResource extends JsonResource
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
            'product_id' => $this->product_id,
            'product_name' => $this->name,
            'product_slug' => $this->whenLoaded('product') ? $this->product->slug : null,
            'variant_id' => $this->variant_id,
            'variant' => $this->when($this->variant_id && $this->whenLoaded('variant'), function() {
                return [
                    'id' => $this->variant->id,
                    'sku' => $this->variant->sku,
                    'option1_value' => $this->variant->option1_value,
                ];
            }),
            'color' => $this->when($this->color_id && $this->whenLoaded('color'), function() {
                return [
                    'id' => $this->color->id,
                    'name' => $this->color->name,
                ];
            }),
            'size' => $this->when($this->size_id && $this->whenLoaded('size'), function() {
                return [
                    'id' => $this->size->id,
                    'name' => $this->size->name,
                    'unit' => $this->size->unit ?? '',
                ];
            }),
            'price' => (float)$this->price,
            'qty' => (int)$this->qty,
            'subtotal' => (float)$this->subtotal,
            'image' => $this->formatImageUrl($this->image),
            'weight' => (float)($this->weight ?? 0),
        ];
    }

    /**
     * Format image URL
     * 
     * @param string|null $image
     * @return string
     */
    private function formatImageUrl(?string $image): string
    {
        if (empty($image)) {
            $r2Domain = config('filesystems.disks.r2.url', '');
            if (!empty($r2Domain)) {
                return rtrim($r2Domain, '/') . '/public/image/no_image.png';
            }
            return asset('/public/image/no_image.png');
        }
        
        $r2Domain = config('filesystems.disks.r2.url', '');
        $r2DomainClean = !empty($r2Domain) ? rtrim($r2Domain, '/') : '';
        
        if (empty($r2DomainClean)) {
            return filter_var($image, FILTER_VALIDATE_URL) ? $image : asset($image);
        }
        
        $image = trim($image);
        $checkR2 = str_replace(['http://', 'https://'], '', $r2DomainClean);
        $cleanPath = str_replace(['http://', 'https://'], '', $image);
        $cleanPath = str_replace($checkR2 . '/', '', $cleanPath);
        $cleanPath = str_replace($checkR2, '', $cleanPath);
        $cleanPath = preg_replace('#/+#', '/', $cleanPath);
        $cleanPath = preg_replace('#(uploads/)+#', 'uploads/', $cleanPath);
        $cleanPath = ltrim($cleanPath, '/');
        
        return $r2DomainClean . '/' . $cleanPath;
    }
}
