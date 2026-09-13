<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $seo = $this->input('seo');

        if (is_array($seo) && array_key_exists('schema_markup', $seo)) {
            $seo['schema_markup'] = $this->normalizeSchemaMarkup($seo['schema_markup']);
            $this->merge(['seo' => $seo]);
        }
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
        $id = request('product');
        return [
            'title' => "required|unique:products,title,{$id}",
            'product_category' => 'required|array|min:1',
            'product_category.*' => 'integer|exists:categories,id',
            'qty' => 'nullable|string|max:50',
            'status' => 'required',
            'seo.schema_markup' => 'nullable|json',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Title is required',
            'product_category.required' => 'Select at least one category',
            'status.required' => 'Status is required',
            'seo.schema_markup.json' => 'Schema must be valid JSON-LD. Paste only JSON, or paste a full application/ld+json script tag.',
        ];
    }

    private function normalizeSchemaMarkup($value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/<script\b[^>]*>(.*?)<\/script>/is', $value, $matches)) {
            $value = trim($matches[1]);
        }

        return $value;
    }
}
