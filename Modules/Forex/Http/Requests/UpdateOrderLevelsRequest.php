<?php

namespace Modules\Forex\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Forex\Models\Position;

class UpdateOrderLevelsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $position = $this->route('position');

        return $position instanceof Position
            && (int) $position->user_id === (int) $this->user()?->id
            && $position->closed_at === null
            && in_array($position->status, ['open', 'pending'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'stop_loss' => ['nullable', 'numeric', 'min:0'],
            'take_profit' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
