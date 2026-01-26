<?php

declare(strict_types=1);
namespace App\Http\Requests\Taxonomy;

use Illuminate\Foundation\Http\FormRequest;

class TaxonomyBulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'checklist' => ['required', 'array', 'min:1'],
            'checklist.*' => ['integer', 'min:1'],
            'action' => ['required', 'integer', 'in:0,1,2'],
        ];
    }
}

