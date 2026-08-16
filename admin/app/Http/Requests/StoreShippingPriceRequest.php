<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'min_order_amount' => "required|regex:/^\d*(\.\d{2})?$/",
            'max_order_amount' => "required|regex:/^\d*(\.\d{2})?$/",
            'shipping_amount' => "required|regex:/^\d*(\.\d{2})?$/",
        ];
    }

}
