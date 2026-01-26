<?php

declare(strict_types=1);

namespace App\Http\Requests\Ingredient;

use Illuminate\Foundation\Http\FormRequest;

class DictionaryBulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'checklist' => ['required', 'array', 'min:1'],
            'checklist.*' => ['integer'],
            'action' => ['required', 'in:0,1,2'],
        ];
    }
}
