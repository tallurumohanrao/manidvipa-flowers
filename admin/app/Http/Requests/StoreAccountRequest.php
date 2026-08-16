<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
{
    protected $errorBag = 'accountForm';
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
        $rules= [
            'name' => 'required|max:255',
            'email' => 'required',
            'image' => 'required_without:old_image|max:8000',
        ];
        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'image.required' => 'Image is required.', 
            'image.required_without' => 'Image is required.',
        ];
    }
}
