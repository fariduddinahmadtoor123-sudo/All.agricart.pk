<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\PakistaniBanks;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegistrationRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'name_ur' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where(
                    fn ($query) => $query->where('status', '!=', User::STATUS_REJECTED),
                ),
            ],
            'address' => ['required', 'string', 'max:2000'],
            'address_ur' => ['required', 'string', 'max:2000'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phones' => ['required', 'array', 'min:1', 'max:5'],
            'phones.*.contact_person' => ['required', 'string', 'max:255'],
            'phones.*.contact_person_ur' => ['required', 'string', 'max:255'],
            'phones.*.phone_number' => ['required', 'string', 'max:30'],
            'banks' => ['required', 'array', 'min:1', 'max:5'],
            'banks.*.bank_name' => ['required', 'string', Rule::in(PakistaniBanks::names())],
            'banks.*.account_title' => ['required', 'string', 'max:255'],
            'banks.*.account_title_ur' => ['required', 'string', 'max:255'],
            'banks.*.account_number' => ['required', 'string', 'max:64'],
            'profile_photo' => ['required', 'image', 'max:5120'],
            'cnic_front' => ['required', 'image', 'max:5120'],
            'cnic_back' => ['required', 'image', 'max:5120'],
            'terms' => ['accepted'],
        ];
    }
}
