<?php

namespace App\Http\Requests;

use App\Support\SeoRouteManager;
use Illuminate\Foundation\Http\FormRequest;

class StorePageRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'url' => SeoRouteManager::normalizePath($this->input('url'), '/'.SeoRouteManager::slugFromPath($this->input('name'))),
        ]);
    }

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
            'url' => 'required|string|max:190',
            //'description' => 'required',
            'status' => 'required|boolean',
        ];
        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => 'Name is required.',
            'url.required' => 'Public page URL is required.',
            'description.required' => 'Description is required.',
            'status.required' => 'Status is required.',
        ];
    }
}
