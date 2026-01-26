<?php

declare(strict_types=1);

namespace App\Http\Requests\Ingredient;

use Illuminate\Foundation\Http\FormRequest;

class DictionaryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:250'],
            'status' => ['required', 'in:0,1'],
            'sort' => ['nullable', 'integer'],
        ];
    }
}
