<?php

namespace Modules\Forex\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Forex\Support\Symbols;

class ClosePositionsRequest extends FormRequest
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
            'side' => ['required', 'string', Rule::in(['profit', 'loss', 'all'])],
        ];
    }
}
