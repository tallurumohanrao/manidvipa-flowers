<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminRequest extends FormRequest
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
        $adminId = $this->route('admin')?->id ?? $this->route('admin');
        $rules= [
            'name' => 'required|max:255',
            'email' => 'required|email|max:191|unique:admins,email,'.$adminId,
            'roles' => 'required|array|min:1',
            'roles.*' => ['integer', Rule::exists('roles', 'id')->where('status', 1)],
            'status' => 'required|in:0,1',
            //'image' => 'required_without:old_image|mimes:jpeg,jpg,png,gif,svg|max:8000',
        ];
        if($this->method()=="POST"){
            $rules['password'] = 'required|string|min:8|confirmed';
        }
        if($this->method()=="PATCH"){
            $rules['password'] = 'nullable|string|min:8|confirmed';
        }
        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => 'Title is required.',
            'email.required' => 'Email is required.',
            'image.required' => 'Image is required.',
            'image.required_without' => 'Image is required.',
        ];
    }
}
