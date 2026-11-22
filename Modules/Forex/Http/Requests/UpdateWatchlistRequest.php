<?php

namespace Modules\Forex\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Forex\Support\Symbols;

class UpdateWatchlistRequest extends FormRequest
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
            'symbols' => ['nullable', 'array', 'max:'.Symbols::max()],
            'symbols.*' => ['string', 'max:32', Rule::in(Symbols::allowed())],
            'custom' => ['nullable', 'string', 'max:32'],
        ];
    }
}
