<?php

namespace Modules\Forex\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Forex\Models\LedgerEntry;

class StoreLedgerEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('note') === '') {
            $this->merge(['note' => null]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_account_id' => ['required', 'integer', Rule::exists('forex_client_accounts', 'id')],
            'type' => ['required', 'string', Rule::in(LedgerEntry::types())],
            'amount' => ['required', 'numeric', 'not_in:0', 'decimal:0,2', 'between:-999999999999.99,999999999999.99'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = (string) $this->input('type');
            $amount = (float) $this->input('amount');

            if (in_array($type, [LedgerEntry::TYPE_DEPOSIT, LedgerEntry::TYPE_WITHDRAWAL], true) && $amount <= 0) {
                $validator->errors()->add('amount', 'Deposit and withdrawal amounts must be positive.');
            }
        });
    }
}
