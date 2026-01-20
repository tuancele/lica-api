<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use App\Enums\ProductStatus;
use App\Enums\ProductType;

/**
 * Form Request for storing a new product
 * 
 * This class handles validation and authorization
 * for product creation requests.
 */
class StoreProductRequest extends FormRequest
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
        return [
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
                'unique:posts,slug',
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
                'url',
                'max:500'
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
            'sku' => [
                'nullable',
                'string',
                'max:100',
                'unique:variants,sku'
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
            'sku.unique' => 'SKU đã tồn tại',
            'imageOther.*.url' => 'URL hình ảnh không hợp lệ',
            'price.numeric' => 'Giá phải là số',
            'price.min' => 'Giá không được nhỏ hơn 0',
        ];
    }

    /**
     * Prepare the data for validation.
     * 
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Auto-generate slug from name if not provided
        if (!$this->has('slug') && $this->has('name')) {
            $this->merge([
                'slug' => Str::slug($this->name)
            ]);
        }

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

    /**
     * Get validated data with defaults
     * 
     * @return array
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated();

        // Set defaults using Enums
        $validated['status'] = $validated['status'] ?? ProductStatus::ACTIVE->value;
        $validated['feature'] = $validated['feature'] ?? '0';
        $validated['best'] = $validated['best'] ?? '0';
        $validated['stock'] = $validated['stock'] ?? '1';
        $validated['type'] = ProductType::PRODUCT->value;
        $validated['user_id'] = $this->user()->id;

        return $validated;
    }
}
