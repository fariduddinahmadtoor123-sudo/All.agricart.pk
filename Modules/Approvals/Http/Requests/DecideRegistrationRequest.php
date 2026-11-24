<?php

namespace Modules\Approvals\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->isSuperAdmin();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->routeIs('approvals.reject')) {
            return [
                'reason' => ['required', 'string', 'max:255'],
            ];
        }

        return [
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
        ];
    }
}
