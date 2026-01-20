<?php

namespace App\Http\Requests\Ingredient;

use Illuminate\Foundation\Http\FormRequest;

class IngredientStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:0,1'],
        ];
    }
}
