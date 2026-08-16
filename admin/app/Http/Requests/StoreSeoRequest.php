<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSeoRequest extends FormRequest
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
        $id = $this->seo->id ?? '';
        $rules= [
            'url' => "required|max:190|unique:seo_urls,url,{$id}",
            //'alias' => 'required',
            'page_title' => 'required',
        ];
        return $rules;
    }

    public function messages()
    {
        return [
            'url.required' => 'URL is required.',
            'alias.required' => 'Alias is required.',
            'page_title.required' => 'Page title is required.',
        ];
    }
}
