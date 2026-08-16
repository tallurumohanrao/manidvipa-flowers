<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddonRequest extends FormRequest
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
            'type' => 'required',
            'name' => 'required|max:191',
            'status' => 'required',
            //'image' => 'required_without:old_image|mimes:jpeg,jpg,png,gif,svg|max:8000',
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => 'Title is required.',
            'type.required' => 'Cake type is required.',
            'status.required' => 'Status is required.'
        ];
    }
}
