<?php

declare(strict_types=1);
namespace App\Modules\Marketing\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingCampaign extends Model
{
    protected $table = 'marketing_campaigns';
    protected $fillable = ['name', 'start_at', 'end_at', 'status', 'user_id'];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function products()
    {
        return $this->hasMany('App\Modules\Marketing\Models\MarketingCampaignProduct', 'campaign_id', 'id');
    }
}
