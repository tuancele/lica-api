<?php

declare(strict_types=1);

namespace App\Modules\Menu\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMenu extends Model
{
    protected $table = 'group_menu';

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function menu()
    {
        return $this->hasMany('App\Modules\Menu\Models\Menu', 'group_id', 'id');
    }
}
