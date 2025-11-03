<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Order;

class UpdateOrderRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->isAdmin();
    }

    public function rules()
    {
        return [
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
            'notes' => 'nullable|string|max:1000',
            'tracking_number' => 'nullable|string|max:100',
        ];
    }

    public function messages()
    {
        return [
            'status.required' => 'Order status is required.',
            'status.in' => 'Please select a valid order status.',
        ];
    }
}
