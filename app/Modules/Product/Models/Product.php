<?php

declare(strict_types=1);

namespace App\Modules\Product\Models;

use App\Modules\FlashSale\Models\FlashSale;
use App\Modules\FlashSale\Models\ProductSale;
use App\Modules\Marketing\Models\MarketingCampaign;
use App\Modules\Marketing\Models\MarketingCampaignProduct;
use App\Services\Warehouse\WarehouseServiceInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'posts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'image',
        'gallery',
        'video',
        'content',
        'description',
        'status',
        'type',
        'has_variants',
        'option1_name',
        'cat_id',
        'brand_id',
        'origin_id',
        'seo_title',
        'seo_description',
        'feature',
        'best',
        'stock',
        'ingredient',
        'verified',
        'cbmp',
        'sort',
        'view',
        'user_id',
        // Packaging dimensions (grams, cm)
        'weight',
        'length',
        'width',
        'height',
    ];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function brand()
    {
        return $this->belongsTo('App\Modules\Brand\Models\Brand', 'brand_id', 'id');
    }

    public function origin()
    {
        return $this->belongsTo('App\Modules\Origin\Models\Origin', 'origin_id', 'id');
    }

    public function variants()
    {
        return $this->hasMany('App\Modules\Product\Models\Variant', 'product_id', 'id');
    }

    public function variant($id)
    {
        return Variant::where('product_id', $id)->first();
    }

    public function arrayCate($id, $type)
    {
        $array = [$id];
        $category = Product::select('id', 'cat_id')->where([['status', '1'], ['cat_id', $id], ['type', $type]])->get();
        if ($category->count() > 0) {
            foreach ($category as $value) {
                array_push($array, $value->id);
                $category2 = Product::select('id', 'cat_id')->where([['status', '1'], ['cat_id', $value->id], ['type', $type]])->get();
                if ($category2->count() > 0) {
                    foreach ($category2 as $value2) {
                        array_push($array, $value2->id);
                    }
                }
            }
        }

        return $array;
    }

    public function category()
    {
        return $this->belongsTo(Product::class, 'cat_id')->select('id', 'name', 'slug');
    }

    public function children()
    {
        return $this->hasMany(Product::class, 'cat_id')->select('id', 'name', 'slug');
    }

    public function rates()
    {
        return $this->hasMany('App\Modules\Rate\Models\Rate', 'product_id', 'id')->where('status', '1');
    }

    /**
     * Get the display price information based on priority:
     * 1. Flash Sale
     * 2. Marketing Campaign
     * 3. Original Price
     *
     * @return object
     */
    public function getPriceInfoAttribute()
    {
        $now = time(); // FlashSale uses timestamp
        $nowDate = Carbon::now(); // Campaign uses timestamp/datetime

        $variant = $this->variant($this->id);
        $originalPrice = $variant ? $variant->price : 0;

        // 1. Check Flash Sale
        // Assuming FlashSale stores timestamp in start/end
        $flashSaleProduct = ProductSale::where('product_id', $this->id)
            ->whereHas('flashsale', function ($q) use ($now) {
                $q->where('status', 1)
                    ->where('start', '<=', $now)
                    ->where('end', '>=', $now);
            })->first();

        if ($flashSaleProduct) {
            return (object) [
                'price' => $flashSaleProduct->price_sale,
                'original_price' => $originalPrice,
                'type' => 'flashsale',
                'label' => 'Flash Sale',
            ];
        }

        // 2. Check Marketing Campaign
        // MarketingCampaign uses standard timestamp or datetime string
        $campaignProduct = MarketingCampaignProduct::where('product_id', $this->id)
            ->whereHas('campaign', function ($q) use ($nowDate) {
                $q->where('status', 1)
                    ->where('start_at', '<=', $nowDate)
                    ->where('end_at', '>=', $nowDate);
            })->first();

        if ($campaignProduct) {
            $campaignPrice = (float) ($campaignProduct->price ?? 0);
            if ($campaignPrice > 0 && $campaignPrice < (float) $originalPrice) {
                return (object) [
                    'price' => $campaignPrice,
                    'original_price' => $originalPrice,
                    'type' => 'promotion',
                    'label' => 'Khuyến mại',
                ];
            }
        }

        // 3. Normal Price
        return (object) [
            'price' => $originalPrice,
            'original_price' => $originalPrice,
            'type' => 'normal',
            'label' => '',
        ];
    }

    /**
     * Check if product is out of stock based on Warehouse stock
     * Uses WarehouseService to get actual stock from inventory_stocks.
     */
    public function getIsAvailableAttribute(): bool
    {
        try {
            $warehouseService = app(WarehouseServiceInterface::class);

            // Get default variant
            $defaultVariant = $this->variant($this->id);
            if (! $defaultVariant) {
                return false;
            }

            // Get stock from Warehouse
            $stockInfo = $warehouseService->getVariantStock($defaultVariant->id);

            // Check available_stock (priority: Flash Sale > Deal > Available)
            $availableStock = (int) ($stockInfo['available_stock'] ?? 0);

            // Also check flash_sale_stock and deal_stock if active
            $flashSaleStock = (int) ($stockInfo['flash_sale_stock'] ?? 0);
            $dealStock = (int) ($stockInfo['deal_stock'] ?? 0);

            // Product is available if any stock > 0
            return $availableStock > 0 || $flashSaleStock > 0 || $dealStock > 0;
        } catch (\Throwable $e) {
            // If error, consider as out of stock for safety
            \Log::error('Product::getIsAvailableAttribute error', [
                'product_id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if product is out of stock (alias for getIsAvailableAttribute).
     */
    public function isOutOfStock(): bool
    {
        return ! $this->is_available;
    }
}
