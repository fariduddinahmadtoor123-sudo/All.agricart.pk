<?php

namespace Modules\Forex\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Forex\Support\Symbols;

class OpenPositionsRequest extends FormRequest
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
            'armed' => ['accepted'],
            'symbol' => ['required', 'string', Rule::in(Symbols::allowed())],
            'side' => ['required', 'string', Rule::in(['buy', 'sell'])],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:1000'],
        ];
    }
}
