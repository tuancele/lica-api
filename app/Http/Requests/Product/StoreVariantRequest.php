<?php

declare(strict_types=1);
namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\Product\Models\Product;

/**
 * Form Request for storing a new variant
 * 
 * This class handles validation and authorization
 * for variant creation requests.
 */
class StoreVariantRequest extends FormRequest
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
        return [
            'sku' => [
                'required',
                'string',
                'max:100',
                'unique:variants,sku'
            ],
            'product_id' => [
                'required',
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
                'required',
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
            'sku.required' => 'SKU không được bỏ trống',
            'sku.unique' => 'SKU đã tồn tại',
            'sku.max' => 'SKU không được vượt quá 100 ký tự',
            'product_id.required' => 'ID sản phẩm không được bỏ trống',
            'product_id.exists' => 'Sản phẩm không tồn tại',
            'price.required' => 'Giá không được bỏ trống',
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
