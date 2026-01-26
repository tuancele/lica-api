<?php

declare(strict_types=1);

namespace App\Themes\Website\Models;

use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    protected $table = 'wishlists';

    public function member()
    {
        return $this->belongsTo('App\Modules\Member\Models\Member', 'member_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo('App\Modules\Product\Models\Product', 'product_id', 'id');
    }
}
