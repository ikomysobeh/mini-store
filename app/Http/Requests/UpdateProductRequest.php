<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->isAdmin();
    }

    public function rules()
    {
        $productId = $this->route('product')->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products')->ignore($productId),
            ],
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0|max:999999.99',
            'stock' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'required|boolean',
            'is_donatable' => 'required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Product name is required.',
            'name.unique' => 'This product name already exists.',
            'price.required' => 'Price is required.',
            'price.numeric' => 'Price must be a valid number.',
            'stock.required' => 'Stock quantity is required.',
            'category_id.required' => 'Please select a category.',
            'image.image' => 'File must be an image.',
            'image.max' => 'Image size must not exceed 2MB.',
        ];
    }
}
