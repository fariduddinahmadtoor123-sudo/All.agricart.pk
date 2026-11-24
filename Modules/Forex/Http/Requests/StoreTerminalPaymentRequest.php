<?php

namespace Modules\Forex\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Forex\Models\Payment;

class StoreTerminalPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->clientAccount !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(Payment::types())],
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2', 'max:999999999999.99'],
        ];
    }
}
