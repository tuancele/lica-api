<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for updating an existing variant
 * 
 * This class handles validation and authorization
 * for variant update requests.
 */
class UpdateVariantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $variantId = $this->route('code'); // Get variant ID from route parameter
        
        return [
            'sku' => [
                'nullable',
                'string',
                'max:100',
                'unique:variants,sku,' . $variantId
            ],
            'product_id' => [
                'nullable',
                'integer',
                'exists:posts,id'
            ],
            'option1_value' => [
                'nullable',
                'string',
                'max:255'
            ],
            'image' => [
                'nullable',
                'url',
                'max:500'
            ],
            'size_id' => [
                'nullable',
                'integer',
                'exists:sizes,id'
            ],
            'color_id' => [
                'nullable',
                'integer',
                'exists:colors,id'
            ],
            'weight' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'price' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'sale' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'stock' => [
                'nullable',
                'integer',
                'min:0'
            ],
            'position' => [
                'nullable',
                'integer',
                'min:0'
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     * 
     * @return array
     */
    public function messages(): array
    {
        return [
            'sku.unique' => 'SKU đã tồn tại',
            'sku.max' => 'SKU không được vượt quá 100 ký tự',
            'product_id.exists' => 'Sản phẩm không tồn tại',
            'price.numeric' => 'Giá phải là số',
            'price.min' => 'Giá không được nhỏ hơn 0',
            'sale.numeric' => 'Giá khuyến mãi phải là số',
            'sale.min' => 'Giá khuyến mãi không được nhỏ hơn 0',
            'size_id.exists' => 'Kích thước không tồn tại',
            'color_id.exists' => 'Màu sắc không tồn tại',
            'image.url' => 'URL hình ảnh không hợp lệ',
        ];
    }
}
