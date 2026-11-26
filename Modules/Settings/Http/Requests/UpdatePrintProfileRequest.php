<?php

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePrintProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:120'],
            'name_ur' => ['nullable', 'string', 'max:120'],
            'width_mm' => ['required', 'numeric', 'min:10', 'max:500'],
            'height_mm' => ['nullable', 'numeric', 'min:10', 'max:500'],
            'margin_mm' => ['required', 'numeric', 'min:0', 'max:40'],
            'orientation' => ['required', Rule::in(['portrait', 'landscape'])],
            'printer_hint' => ['nullable', 'string', 'max:255'],
        ];
    }
}
