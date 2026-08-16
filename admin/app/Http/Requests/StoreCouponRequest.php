<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCouponRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */

    public function rules(): array
    {
        $id = request('coupon');
        $rules= [
            'title' => "required|unique:coupons,title,$id",
            'coupon_code' => "required|unique:coupons,coupon_code,$id",
            'discount' => 'required',
            'status' => 'required',
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'title.required' => 'Title is required.',
            'title.unique' => 'Title has already been taken.',
            'coupon_code.required' => 'Coupon is required.',
            'coupon_code.unique' => 'Coupon has already been taken.',
            'discount.required' => 'Discount is required.',
            'status.required' => 'Status is required.'
        ];
    }
}
