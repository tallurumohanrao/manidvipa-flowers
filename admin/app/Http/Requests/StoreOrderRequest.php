<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'has_billing_address' => 'required',
            'has_shipping_address' => 'required',
            'paymentMethod' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'has_billing_address.required' => 'Billing address is required',
            'has_shipping_address.required' => 'Shipping address is required',
            'paymentMethod.required' => 'Payment method is required',
        ];
    }
}
