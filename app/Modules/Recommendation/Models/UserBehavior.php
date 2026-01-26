<?php

declare(strict_types=1);
namespace App\Modules\Recommendation\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Product\Models\Product;

class UserBehavior extends Model
{
    protected $table = 'user_behaviors';
    
    protected $fillable = [
        'session_id',
        'user_id',
        'product_id',
        'behavior_type',
        'ip_address',
        'user_agent',
        'referrer',
        'duration',
        'country',
        'region',
        'city',
        'device_type',
        'browser',
        'os',
        'page_url',
        'page_title',
        'product_categories',
        'product_brand_id',
        'product_ingredients',
        'product_features',
        'scroll_depth',
        'clicked_product',
        'viewed_gallery',
        'read_description',
        'session_page_views',
        'session_start_time',
    ];

    protected $casts = [
        'product_categories' => 'array',
        'product_ingredients' => 'array',
        'product_features' => 'array',
        'clicked_product' => 'boolean',
        'viewed_gallery' => 'boolean',
        'read_description' => 'boolean',
        'session_start_time' => 'datetime',
    ];

    /**
     * 行为类型常量
     */
    const TYPE_VIEW = 'view';
    const TYPE_CLICK = 'click';
    const TYPE_ADD_TO_CART = 'add_to_cart';
    const TYPE_PURCHASE = 'purchase';

    /**
     * 关联产品
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }
}
