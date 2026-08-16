<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDecorationRequest extends FormRequest
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
        $rules= [
            'name' => 'required|max:191',
            'price' => 'required',
            'status' => 'required',
            'image' => 'required_without:old_image|max:8000',
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => 'Name is required.',
            'price.required' => 'Price is required.',
            'status.required' => 'Status is required.'
        ];
    }
}
