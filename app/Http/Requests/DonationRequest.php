<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DonationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Anyone can donate
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Get minimum amount from settings
        $minAmount = \App\Models\Setting::get('donation_min_amount', 5);

        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'value' => "required|numeric|min:{$minAmount}",
            'message' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        $minAmount = \App\Models\Setting::get('donation_min_amount', 5);

        return [
            'name.required' => 'Please enter your full name.',
            'phone.required' => 'Phone number is required.',
            'value.required' => 'Please enter a donation amount.',
            'value.min' => "Minimum donation amount is \${$minAmount}.",
        ];
    }
}
