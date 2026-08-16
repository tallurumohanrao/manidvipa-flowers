<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnitRequest extends FormRequest
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
        $id = request('unit') ?? '';
        $rules= [
            'name' => "required|unique:weights,name,{$id}",
        ];
        return $rules;
    }

    public function messages()
    {
        return [];
    }
}
