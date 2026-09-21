<?php

namespace App\Http\Requests;

use App\Support\SeoRouteManager;
use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'url' => SeoRouteManager::normalizePath($this->input('url'), '/'.SeoRouteManager::slugFromPath($this->input('title'))),
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
        return [
            'title' => "required",
            'url' => 'required|string|max:190',
            'parent_id' => 'nullable|exists:categories,id',
            'home_category' => 'nullable|boolean',
            'status' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Title is required',
            'url.required' => 'Public category URL is required.',
            'status.required' => 'Status is required',
        ];
    }
}
