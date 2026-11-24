<?php

namespace Modules\Forex\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMt5RatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        foreach (['partner_rate', 'customer_rate'] as $field) {
            if ($this->input($field) === '' || $this->input($field) === null) {
                $this->merge([$field => 0]);
            }
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'partner_rate' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:9999.99'],
            'customer_rate' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:9999.99'],
        ];
    }
}
