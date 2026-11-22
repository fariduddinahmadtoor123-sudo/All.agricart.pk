<?php

namespace Modules\Forex\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Forex\Support\Symbols;

class PlacePendingOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'symbol' => ['required', 'string', Rule::in(Symbols::allowed())],
            'order_type' => ['required', 'string', Rule::in(['buy_limit', 'sell_limit', 'buy_stop', 'sell_stop'])],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:1000'],
            'trigger_price' => ['required', 'numeric', 'gt:0'],
            'stop_loss' => ['nullable', 'numeric', 'min:0'],
            'take_profit' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
