<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreShippingPriceRequest extends FormRequest
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
        $id = request('shippingprice');
        return [
            'title' => "required|max:190|unique:shipping_prices,title,$id",
            'from_km' => 'nullable|numeric|min:0|max:255',
            'to_km' => 'nullable|numeric|min:0|max:255',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_order_amount' => 'nullable|numeric|min:0|gte:min_order_amount',
            'shipping_amount' => 'required|numeric|min:0',
            'status' => 'required|in:0,1',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $fromKm = request('from_km');
            $toKm = request('to_km');
            $shippingAmount = (float) request('shipping_amount');

            if($shippingAmount > 0 && ($fromKm === null || $fromKm === '' || $toKm === null || $toKm === '')){
                $validator->errors()->add('from_km', 'From KM and To KM are required for paid delivery charges.');
            }

            if($fromKm !== null && $fromKm !== '' && $toKm !== null && $toKm !== '' && (float) $toKm <= (float) $fromKm){
                $validator->errors()->add('to_km', 'To KM must be greater than From KM.');
            }
        });
    }
}
