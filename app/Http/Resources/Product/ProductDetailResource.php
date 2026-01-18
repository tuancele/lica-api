<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Product Detail Resource for API V1 responses
 * 
 * Formats complete product detail data including variants, ratings, 
 * flash sale, deals, and related products.
 */
class ProductDetailResource extends JsonResource
{
    /**
     * Additional data to merge into the resource array
     * 
     * @var array
     */
    protected $additionalData = [];

    /**
     * Create a new resource instance with additional data
     * 
     * @param  mixed  $resource
     * @param  array  $additionalData
     * @return void
     */
    public function __construct($resource, array $additionalData = [])
    {
        parent::__construct($resource);
        $this->additionalData = $additionalData;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        // Get gallery as array
        $gallery = $this->getGalleryArray();
        
        // Get categories as array
        $categories = $this->getCategoriesArray();
        
        // Get cart information from session
        $cartInfo = $this->getCartInfo($request);
        
        // Base product data
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => $this->image,
            'video' => $this->video,
            'gallery' => $gallery,
            'description' => $this->description,
            'content' => $this->content,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'stock' => (int) $this->stock,
            'best' => (int) $this->best,
            'is_new' => (int) ($this->is_new ?? 0),
            'cbmp' => $this->cbmp,
            'option1_name' => $this->option1_name,
            'has_variants' => (int) $this->has_variants,
            'brand' => $this->when($this->relationLoaded('brand') && $this->brand, function () {
                return [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name,
                    'slug' => $this->brand->slug,
                    'image' => $this->brand->image ?? null,
                    'logo' => $this->brand->logo ?? null,
                ];
            }),
            'origin' => $this->when($this->relationLoaded('origin') && $this->origin, function () {
                return [
                    'id' => $this->origin->id,
                    'name' => $this->origin->name,
                ];
            }),
            'categories' => $categories,
            'cart' => $cartInfo, // Add cart information
        ];

        // Merge additional data (variants, rating, flash_sale, deal, related_products, etc.)
        return array_merge($data, $this->additionalData);
    }
    
    /**
     * Get cart information from session
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    private function getCartInfo($request): array
    {
        if (!$request->hasSession() || !$request->session()->has('cart')) {
            return [
                'has_cart' => false,
                'total_qty' => 0,
                'items_count' => 0,
            ];
        }
        
        $cart = $request->session()->get('cart');
        $itemsCount = is_object($cart) && isset($cart->items) ? count($cart->items) : 0;
        $totalQty = is_object($cart) && isset($cart->totalQty) ? (int)$cart->totalQty : 0;
        
        return [
            'has_cart' => true,
            'total_qty' => $totalQty,
            'items_count' => $itemsCount,
        ];
    }

    /**
     * Get gallery as array
     * 
     * @return array
     */
    private function getGalleryArray(): array
    {
        $gallery = json_decode($this->gallery ?? '[]', true);
        return is_array($gallery) ? $gallery : [];
    }

    /**
     * Get categories as array
     * 
     * @return array
     */
    private function getCategoriesArray(): array
    {
        $catIds = json_decode($this->cat_id ?? '[]', true);
        return is_array($catIds) ? $catIds : [];
    }
}
