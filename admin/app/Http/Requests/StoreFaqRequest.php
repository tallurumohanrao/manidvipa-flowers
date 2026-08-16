<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFaqRequest extends FormRequest
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
        $id = $this->faq->id ?? '';
        return [
            'question' => "required|unique:faqs,question,$id",
            'answer' => 'required',
            'status' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'question.required' => 'Question is required',
            'answer.required' => 'Answer is required',
            'status.required' => 'Status is required',
        ];
    }
}
