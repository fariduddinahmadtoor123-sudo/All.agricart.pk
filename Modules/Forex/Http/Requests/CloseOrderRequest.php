<?php

namespace Modules\Forex\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Forex\Models\Position;

class CloseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $position = $this->route('position');

        return $position instanceof Position
            && (int) $position->user_id === (int) $this->user()?->id
            && $position->status === Position::STATUS_OPEN
            && $position->closed_at === null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
