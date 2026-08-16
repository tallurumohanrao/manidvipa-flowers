<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
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
        $id = $this->post->id ?? '';
        return [
            'title' => "required|max:255|unique:posts,title,{$id}",
            //'category' => 'required',
            //'short_description' => 'required',
            'description' => 'required',
            'status' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Title is required',
            'category.required' => 'Category is required',
            'short_description.required' => 'Short description is required',
            'description.required' => 'Description is required',
            'status.required' => 'Status is required',
        ];
    }
}
