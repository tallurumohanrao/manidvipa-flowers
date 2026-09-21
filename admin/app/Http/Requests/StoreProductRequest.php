<?php

namespace App\Http\Requests;

use App\Support\SeoRouteManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $title = preg_replace('/\s+/u', ' ', trim((string) $this->input('title')));
        $productId = $this->route('product') ?? $this->route('id') ?? $this->input('product');
        $sku = $this->normalizeSku($this->input('sku'));

        if ($sku === '') {
            $sku = $this->nextSku($title ?: 'product', $productId);
        }

        $seo = $this->input('seo');

        if (is_array($seo) && array_key_exists('schema_markup', $seo)) {
            $seo['schema_markup'] = $this->normalizeSchemaMarkup($seo['schema_markup']);
        }

        if (is_array($seo)) {
            $url = trim((string) ($seo['url'] ?? ''));
            $seo['url'] = $url !== '' ? SeoRouteManager::normalizePath($url) : null;
        }

        $this->merge([
            'title' => $title,
            'sku' => $sku,
            'seo' => $seo,
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
        $id = $this->route('product') ?? $this->route('id') ?? $this->input('product');

        return [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'title')->ignore($id),
            ],
            'sku' => [
                'required',
                'string',
                'max:64',
                'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/',
                Rule::unique('products', 'sku')->ignore($id),
            ],
            'product_category' => 'required|array|min:1',
            'product_category.*' => 'integer|exists:categories,id',
            'qty' => 'nullable|string|max:50',
            'price_visibility' => 'required|in:inherit,show_everywhere,details_only,show_after_selection,enquiry_only,coming_soon',
            'price_visible_from' => 'nullable|date',
            'status' => 'required',
            'seo.url' => 'required|string|max:190',
            'seo.schema_markup' => 'nullable|json',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Title is required',
            'sku.required' => 'SKU is required. Leave it blank to generate one automatically.',
            'sku.regex' => 'SKU may contain only uppercase letters, numbers, and hyphens.',
            'sku.unique' => 'This SKU is already assigned to another product.',
            'product_category.required' => 'Select at least one category',
            'status.required' => 'Status is required',
            'price_visibility.required' => 'Choose how the customer price should be displayed.',
            'seo.url.required' => 'Public product URL is required.',
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

    private function normalizeSku($value): string
    {
        $value = strtoupper(trim((string) $value));

        if ($value === '') {
            return '';
        }

        $value = Str::ascii($value);
        $value = preg_replace('/[^A-Z0-9]+/', '-', $value);

        return trim($value, '-');
    }

    private function nextSku(string $title, $productId = null): string
    {
        $base = 'MF-'.Str::upper(Str::slug($title));
        $base = trim($base, '-');
        $base = $base !== 'MF' ? $base : 'MF-PRODUCT';
        $candidate = $base;
        $suffix = 2;

        while (DB::table('products')
            ->where('sku', $candidate)
            ->when($productId, fn ($query) => $query->where('id', '<>', $productId))
            ->exists()) {
            $candidate = $base.'-'.$suffix++;
        }

        return $candidate;
    }
}
