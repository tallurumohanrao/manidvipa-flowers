<?php

namespace App\Http\Requests;

use App\Support\SeoRouteManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSeoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $seo = $this->route('seo');
        $alias = $this->input('alias') ?: ($seo?->alias ?: $seo?->url ?: $this->input('url'));
        $url = trim((string) $this->input('url'));

        $this->merge([
            'url' => $url !== '' ? SeoRouteManager::normalizePath($url) : null,
            'alias' => $alias ? SeoRouteManager::normalizePath($alias) : null,
        ]);

        if ($this->has('schema_markup')) {
            $this->merge([
                'schema_markup' => $this->normalizeSchemaMarkup($this->input('schema_markup')),
            ]);
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
        $id = $this->route('seo')?->id;
        $rules= [
            'url' => ['required', 'max:190', Rule::unique('seo_urls', 'url')->ignore($id)],
            'alias' => ['required', 'max:190', Rule::unique('seo_urls', 'alias')->ignore($id)],
            'page_title' => ['required', 'max:255'],
            'meta_keywords' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string'],
            'schema_markup' => 'nullable|json',
            'robots' => ['nullable', 'string', 'max:25'],
            'status' => ['required', 'boolean'],
        ];
        return $rules;
    }

    public function messages()
    {
        return [
            'url.required' => 'URL is required.',
            'alias.required' => 'System page path is required.',
            'page_title.required' => 'Page title is required.',
            'schema_markup.json' => 'Schema must be valid JSON-LD. Paste only JSON, or paste a full application/ld+json script tag.',
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
