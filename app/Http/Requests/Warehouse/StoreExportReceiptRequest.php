<?php

declare(strict_types=1);
namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Form Request for storing a new export receipt
 * 
 * This class handles validation and authorization
 * for export receipt creation requests.
 */
class StoreExportReceiptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:100',
                'unique:warehouse,code'
            ],
            'subject' => [
                'required',
                'string',
                'max:255'
            ],
            'content' => [
                'nullable',
                'string'
            ],
            'vat_invoice' => [
                'nullable',
                'string',
                'max:100'
            ],
            'items' => [
                'required',
                'array',
                'min:1'
            ],
            'items.*.variant_id' => [
                'required',
                'integer',
                'exists:variants,id'
            ],
            'items.*.price' => [
                'required',
                'numeric',
                'min:0'
            ],
            'items.*.quantity' => [
                'required',
                'integer',
                'min:1'
            ]
        ];
    }

    /**
     * Configure the validator instance.
     * 
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Stock validation will be done in service layer
            // This allows for more detailed error messages
        });
    }

    /**
     * Get custom messages for validator errors.
     * 
     * @return array
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Bạn chưa nhập mã đơn hàng.',
            'code.unique' => 'Mã đơn hàng đã tồn tại',
            'code.max' => 'Mã đơn hàng không được vượt quá 100 ký tự',
            'subject.required' => 'Bạn chưa nhập tiêu đề.',
            'subject.max' => 'Tiêu đề không được vượt quá 255 ký tự',
            'items.required' => 'Vui lòng thêm ít nhất một sản phẩm.',
            'items.min' => 'Vui lòng thêm ít nhất một sản phẩm.',
            'items.*.variant_id.required' => 'Vui lòng chọn phân loại cho tất cả sản phẩm.',
            'items.*.variant_id.exists' => 'Phân loại không hợp lệ.',
            'items.*.price.required' => 'Vui lòng nhập giá xuất.',
            'items.*.price.numeric' => 'Giá xuất phải là số.',
            'items.*.price.min' => 'Giá xuất không được nhỏ hơn 0.',
            'items.*.quantity.required' => 'Vui lòng nhập số lượng.',
            'items.*.quantity.integer' => 'Số lượng phải là số nguyên.',
            'items.*.quantity.min' => 'Số lượng phải lớn hơn 0.',
        ];
    }
}
