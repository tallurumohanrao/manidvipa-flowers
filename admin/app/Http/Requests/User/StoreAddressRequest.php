<?php

namespace App\Http\Requests\User;

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
            'full_name' => 'required',
            'email' => 'required|email',
            'phone_number' => 'required',
            'address_line1' => 'required',
            'address_line2' => 'required',
            'city' => 'required',
            'pincode' => 'required',
            'state' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'address_line1.required' => 'Flat, House no., Building, Apartment is required',
            'address_line2.required' => 'Area, Street, Sector, Village is required',
        ];
    }
}
