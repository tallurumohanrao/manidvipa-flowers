<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartRequest extends FormRequest
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
            'product_id' => "required",
            'size_id' => 'required',
            'frame' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'product_id.required' => 'Product is required',
            'size_id.required' => 'Size is required',
            'frame.required' => 'Frame is required',
        ];
    }
}
