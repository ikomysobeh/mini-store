<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'required|string|max:500',
            'is_donation' => 'required|boolean',
            'payment_method_id' => 'required|string',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages()
    {
        return [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'address.required' => 'Address is required.',
            'is_donation.required' => 'Please select purchase or donation.',
            'payment_method_id.required' => 'Payment method is required.',
        ];
    }
}
