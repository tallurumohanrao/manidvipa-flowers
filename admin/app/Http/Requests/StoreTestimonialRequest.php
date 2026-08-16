<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTestimonialRequest extends FormRequest
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
            'name' => 'required',
            'designation' => 'required',
            'description' => 'required',
            'image' => 'required_without:old_image',
            'status' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'image.required_without' => 'Image is required',
            'status.required' => 'Status is required',
        ];
    }
}
