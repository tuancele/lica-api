<?php

declare(strict_types=1);

namespace App\Modules\FooterBlock\Models;

use Illuminate\Database\Eloquent\Model;

class FooterBlock extends Model
{
    protected $table = 'footer_blocks';

    protected $fillable = [
        'title',
        'tags',
        'links',
        'status',
        'sort',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    protected $casts = [
        'tags' => 'array',
        'links' => 'array',
    ];

    public function getTagsAttribute($value)
    {
        if (is_null($value) || $value === '') {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function getLinksAttribute($value)
    {
        if (is_null($value) || $value === '') {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
