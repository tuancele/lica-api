<?php

declare(strict_types=1);

namespace App\Modules\Taxonomy\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'posts';

    /**
     * Allow mass-assignment for taxonomy fields.
     *
     * Note: This model shares the "posts" table with other modules,
     * but is only used for taxonomy rows (type = 'taxonomy').
     */
    protected $fillable = [
        'name',
        'slug',
        'image',
        'content',
        'status',
        'feature',
        'is_home',
        'tracking',
        'type',
        'cat_id',
        'seo_title',
        'seo_description',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function childen()
    {
        return $this->hasMany(Category::class, 'cat_id')->select('id', 'name', 'slug', 'image')->where([['type', 'taxonomy'], ['status', '1']])->orderBy('sort', 'asc');
    }
}
