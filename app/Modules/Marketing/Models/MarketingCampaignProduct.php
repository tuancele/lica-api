<?php

declare(strict_types=1);
namespace App\Modules\Marketing\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingCampaignProduct extends Model
{
    protected $table = 'marketing_campaign_products';
    protected $fillable = ['campaign_id', 'product_id', 'price', 'limit'];

    public function campaign()
    {
        return $this->belongsTo('App\Modules\Marketing\Models\MarketingCampaign', 'campaign_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo('App\Modules\Product\Models\Product', 'product_id', 'id');
    }
}
