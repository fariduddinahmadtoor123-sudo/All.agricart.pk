<?php

namespace Modules\Forex\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Forex\Models\Payment;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $reference = trim((string) $this->input('reference', ''));

        $this->merge(['reference' => $reference === '' ? null : $reference]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_account_id' => ['required', 'integer', Rule::exists('forex_client_accounts', 'id')],
            'type' => ['required', 'string', Rule::in(Payment::types())],
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2', 'max:999999999999.99'],
            'reference' => ['required', 'string', 'max:255'],
        ];
    }
}
