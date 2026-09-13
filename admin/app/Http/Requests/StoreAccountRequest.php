<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'email' => ['required', 'email', 'max:191', Rule::unique('admins', 'email')->ignore(auth('admin')->id())],
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ];
        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
        ];
    }
}
