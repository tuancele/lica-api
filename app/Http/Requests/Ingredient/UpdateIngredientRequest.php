<?php

declare(strict_types=1);
namespace App\Http\Requests\Ingredient;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIngredientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => ['required', 'string', 'min:1', 'max:250'],
            'slug' => [
                'required',
                'string',
                'min:1',
                'max:250',
                Rule::unique('ingredient_paulas', 'slug')->ignore($id),
            ],
            'status' => ['required', 'in:0,1'],
            'rate_id' => ['nullable', 'integer', 'exists:ingredient_rate,id'],
            'cat_id' => ['nullable', 'array'],
            'cat_id.*' => ['integer', 'exists:ingredient_category,id'],
            'benefit_id' => ['nullable', 'array'],
            'benefit_id.*' => ['integer', 'exists:ingredient_benefit,id'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'shortcode' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'glance' => ['nullable', 'string'],
            'reference' => ['nullable', 'string'],
            'disclaimer' => ['nullable', 'string'],
        ];
    }
}
