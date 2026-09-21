<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => Str::slug((string) ($this->code ?: $this->singular_name)),
            'allows_decimal' => $this->boolean('allows_decimal'),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('unit');

        return [
            'code' => ['required', 'max:40', 'regex:/^[a-z0-9-]+$/', Rule::unique('measurement_units', 'code')->ignore($id)],
            'singular_name' => ['required', 'string', 'max:80'],
            'plural_name' => ['required', 'string', 'max:80'],
            'type' => ['required', Rule::in(['count', 'weight', 'volume', 'package'])],
            'base_code' => ['nullable', 'string', 'max:40', 'exists:measurement_units,code'],
            'conversion_factor' => ['required', 'numeric', 'gt:0', 'max:100000000'],
            'allows_decimal' => ['nullable', 'boolean'],
            'priority' => ['required', 'integer', 'min:0', 'max:999999'],
            'status' => ['required', 'boolean'],
        ];
    }
}
