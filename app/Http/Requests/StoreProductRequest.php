<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->isAdmin();
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
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
            'price.required' => 'Price is required.',
            'price.numeric' => 'Price must be a valid number.',
            'price.min' => 'Price must be 0 or greater.',
            'stock.required' => 'Stock quantity is required.',
            'stock.min' => 'Stock must be 0 or greater.',
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'Selected category does not exist.',
            'image.image' => 'File must be an image.',
            'image.max' => 'Image size must not exceed 2MB.',
        ];
    }
}
