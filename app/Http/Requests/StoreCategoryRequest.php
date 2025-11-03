<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->isAdmin();
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'required|boolean',
            'sort_order' => 'required|integer|min:0',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Category name is required.',
            'name.unique' => 'This category name already exists.',
            'sort_order.required' => 'Sort order is required.',
            'sort_order.min' => 'Sort order must be 0 or greater.',
        ];
    }
}
