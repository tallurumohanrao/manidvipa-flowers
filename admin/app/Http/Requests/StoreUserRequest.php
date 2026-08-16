<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
        $id = $this->user->id ?? '';
        // return [
        //     'name' => 'required|unique:admins|max:255',
        //     'email' => "required|max:191|unique:users,email,{$id}",
        // ];

        $rules= [
            'name' => 'required|max:255',
            'email' => "required|max:191|unique:users,email,{$id}",
            //'image' => 'required_without:old_image|mimes:jpeg,jpg,png,gif,svg|max:8000',
        ];
        if($this->method()=="POST"){
            $rules['password'] = 'required|string|min:6|confirmed';
        }
        if($this->method()=="PATCH"){
            $rules['password'] = 'nullable|string|min:6|confirmed';
        }
        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => 'Title is required',
            'email.required' => 'Email is required',
            'email.unique' => 'Email already existed.',
        ];
    }
}
