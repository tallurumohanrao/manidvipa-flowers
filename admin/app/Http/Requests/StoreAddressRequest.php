<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
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
            'address.full_name' => 'required',
            'address.email' => 'required|email',
            'address.phone_number' => 'required',
            'address.address_line1' => 'required',
            'address.address_line2' => 'required',
            'address.city' => 'required',
            'address.pincode' => 'required',
            'address.state' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'address.address_line1.required' => 'Flat, House no., Building, Apartment is required',
            'address.address_line2.required' => 'Area, Street, Sector, Village is required',
        ];
    }
}
