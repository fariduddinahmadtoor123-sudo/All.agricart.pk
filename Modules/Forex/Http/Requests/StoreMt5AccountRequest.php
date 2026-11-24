<?php

namespace Modules\Forex\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMt5AccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('company') === '') {
            $this->merge(['company' => null]);
        }

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
            'name' => ['required', 'string', 'max:120'],
            'broker' => ['required', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:120'],
            'server' => ['required', 'string', 'max:120'],
            'login' => ['required', 'string', 'max:60'],
            'partner_rate' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:9999.99'],
            'customer_rate' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:9999.99'],
        ];
    }
}
