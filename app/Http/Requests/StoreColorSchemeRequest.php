<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreColorSchemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $colors = [];

        foreach (['page', 'card', 'soft', 'line', 'primary'] as $field) {
            $value = trim((string) $this->input($field));

            if ($value !== '' && ! str_starts_with($value, '#')) {
                $value = '#'.$value;
            }

            $colors[$field] = strtolower($value);
        }

        $this->merge($colors);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('color_schemes', 'name')->ignore($this->route('colorScheme'))],
            'page' => ['required', 'regex:/^#[0-9a-f]{6}$/'],
            'card' => ['required', 'regex:/^#[0-9a-f]{6}$/'],
            'soft' => ['required', 'regex:/^#[0-9a-f]{6}$/'],
            'line' => ['required', 'regex:/^#[0-9a-f]{6}$/'],
            'primary' => ['required', 'regex:/^#[0-9a-f]{6}$/'],
        ];
    }
}
