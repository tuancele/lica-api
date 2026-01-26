<?php

declare(strict_types=1);
namespace App\Http\Requests\Taxonomy;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxonomyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:250'],
            'slug' => ['required', 'string', 'min:1', 'max:250', 'unique:posts,slug'],
            'image' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'status' => ['required', 'integer', 'in:0,1'],
            'feature' => ['nullable', 'integer', 'in:0,1'],
            'is_home' => ['nullable', 'integer', 'in:0,1'],
            'tracking' => ['nullable', 'string', 'max:500'],
            'cat_id' => ['nullable', 'integer'],
            'seo_title' => ['nullable', 'string', 'max:250'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ];
    }
}

