<?php

namespace App\Themes\Website\Models;

use Illuminate\Database\Eloquent\Model;

class MemberPromotion extends Model
{
    protected $table = "member_promotion";
    public function member(){
    	return $this->belongsTo('App\Modules\Member\Models\Member','member_id','id');
    }

    public function promotion(){
    	return $this->belongsTo('App\Modules\Promotion\Models\Promotion','promotion_id','id');
    }
}
