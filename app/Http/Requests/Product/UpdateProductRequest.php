<?php

declare(strict_types=1);
namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Modules\Product\Models\Product;

/**
 * Form Request for updating an existing product
 * 
 * This class handles validation and authorization
 * for product update requests.
 */
class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        // For now, allow all authenticated users
        // Can be enhanced with proper policy later
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        // Get product ID from route parameter (URL) instead of request body
        $productId = $this->route('id') ?? $this->input('id');
        
        // Ensure productId is not null for slug unique validation
        if (!$productId) {
            \Log::warning('UpdateProductRequest: productId is null', [
                'route_params' => $this->route()->parameters(),
                'input_id' => $this->input('id'),
            ]);
        }
        
        return [
            // Note: 'id' is not required in body since it comes from URL route parameter
            // Controller will merge it from route parameter
            'name' => [
                'required',
                'string',
                'min:1',
                'max:250'
            ],
            'slug' => [
                'required',
                'string',
                'min:1',
                'max:250',
                'unique:posts,slug,' . $productId,
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'
            ],
            'content' => [
                'nullable',
                'string'
            ],
            'description' => [
                'nullable',
                'string',
                'max:500'
            ],
            'video' => [
                'nullable',
                'url',
                'max:500'
            ],
            'imageOther' => [
                'nullable',
                'array'
            ],
            'imageOther.*' => [
                'nullable',
                'string',
                'max:500'
                // Note: Accept both relative paths (/uploads/...) and absolute URLs (http://...)
                // Removed 'url' rule to allow relative paths
            ],
            'imageOtherRemoved' => [
                'nullable',
                'array'
            ],
            'imageOtherRemoved.*' => [
                'nullable',
                'string',
                'max:500'
                // Note: Accept both relative paths and absolute URLs
            ],
            'cat_id' => [
                'nullable',
                'array'
            ],
            'cat_id.*' => [
                'integer',
                'exists:posts,id'
            ],
            'brand_id' => [
                'nullable',
                'integer',
                'exists:brands,id'
            ],
            'origin_id' => [
                'nullable',
                'integer',
                'exists:origins,id'
            ],
            'ingredient' => [
                'nullable',
                'string'
            ],
            'cbmp' => [
                'nullable',
                'string',
                'max:250'
            ],
            'price' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'stock_qty' => [
                'nullable',
                'integer',
                'min:0'
            ],
            'weight' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'length' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'width' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'height' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                // Không bắt buộc và không check unique ở FormRequest;
                // phần SKU của biến thể sẽ được ProductService tự xử lý và đảm bảo unique.
            ],
            'has_variants' => [
                'nullable',
                'in:0,1'
            ],
            'option1_name' => [
                'nullable',
                'string',
                'max:50',
                'required_if:has_variants,1'
            ],
            'variants_json' => [
                'nullable',
                'string',
                'required_if:has_variants,1'
            ],
            'status' => [
                'nullable',
                'in:0,1'
            ],
            'feature' => [
                'nullable',
                'in:0,1'
            ],
            'best' => [
                'nullable',
                'in:0,1'
            ],
            'stock' => [
                'nullable',
                'in:0,1'
            ],
            'seo_title' => [
                'nullable',
                'string',
                'max:250'
            ],
            'seo_description' => [
                'nullable',
                'string',
                'max:500'
            ],
            'r2_session_key' => [
                'nullable',
                'string'
            ]
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
            'id.required' => 'ID sản phẩm không được bỏ trống.',
            'id.exists' => 'Sản phẩm không tồn tại.',
            'name.required' => 'Tiêu đề không được bỏ trống.',
            'name.min' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'name.max' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'slug.required' => 'Bạn chưa nhập đường dẫn',
            'slug.min' => 'Đường dẫn có độ dài từ 1 đến 250 ký tự',
            'slug.max' => 'Đường dẫn có độ dài từ 1 đến 250 ký tự',
            'slug.unique' => 'Đường dẫn đã tồn tại',
            'slug.regex' => 'Đường dẫn không hợp lệ. Chỉ chấp nhận chữ thường, số và dấu gạch ngang.',
            'cat_id.*.exists' => 'Danh mục không tồn tại',
            'brand_id.exists' => 'Thương hiệu không tồn tại',
            'origin_id.exists' => 'Xuất xứ không tồn tại',
            'imageOther.*.max' => 'Đường dẫn hình ảnh không được vượt quá 500 ký tự',
            'imageOtherRemoved.*.max' => 'Đường dẫn hình ảnh không được vượt quá 500 ký tự',
        ];
    }

    /**
     * Handle a failed validation attempt.
     * 
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        // Log validation errors for debugging
        \Log::warning('Product update validation failed', [
            'errors' => $validator->errors()->toArray(),
            'input' => $this->all(),
            'route_id' => $this->route('id'),
        ]);
        
        parent::failedValidation($validator);
    }

    /**
     * Prepare the data for validation.
     * 
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Ensure slug is lowercase
        if ($this->has('slug')) {
            $this->merge([
                'slug' => Str::slug($this->slug)
            ]);
        }

        // Convert price strings to numbers (remove commas)
        if ($this->has('price') && is_string($this->price)) {
            $this->merge([
                'price' => (float) str_replace(',', '', $this->price)
            ]);
        }
        // Normalize has_variants
        if ($this->has('has_variants')) {
            $this->merge([
                'has_variants' => (string) $this->input('has_variants')
            ]);
        } else {
            $this->merge(['has_variants' => '0']);
        }
    }
}
