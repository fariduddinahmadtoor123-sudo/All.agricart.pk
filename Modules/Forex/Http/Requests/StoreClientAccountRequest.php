<?php

namespace Modules\Forex\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('mt5_account_id') === '' || $this->input('mt5_account_id') === null) {
            $this->merge(['mt5_account_id' => null]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
                Rule::unique('forex_client_accounts', 'user_id'),
            ],
            'mt5_account_id' => ['nullable', 'integer', Rule::exists('forex_mt5_accounts', 'id')->where('active', true)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.unique' => 'This user already has a client account.',
        ];
    }
}
