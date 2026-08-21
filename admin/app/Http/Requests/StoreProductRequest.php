<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
        $id = request('product');
        return [
            'title' => "required|unique:products,title,{$id}",
            'product_category' => 'required|array|min:1',
            'product_category.*' => 'integer|exists:categories,id',
            'status' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Title is required',
            'product_category.required' => 'Select at least one category',
            'status.required' => 'Status is required',
        ];
    }
}
