<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegistrationRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Forex\Support\WalletBook;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(StoreRegistrationRequest $request, WalletBook $wallets): RedirectResponse
    {
        $email = mb_strtolower(trim((string) $request->string('email')));
        $existing = User::query()
            ->where('email', $email)
            ->where('status', User::STATUS_REJECTED)
            ->first();

        $paths = [
            'profile_photo_path' => $request->file('profile_photo')->store('users/profile-photos', 'local'),
            'cnic_front_path' => $request->file('cnic_front')->store('users/cnic', 'local'),
            'cnic_back_path' => $request->file('cnic_back')->store('users/cnic', 'local'),
        ];

        $details = [
            'name' => $request->string('name')->toString(),
            'name_ur' => $request->string('name_ur')->toString(),
            'email' => $email,
            'address' => $request->string('address')->toString(),
            'address_ur' => $request->string('address_ur')->toString(),
            'password' => $request->string('password')->toString(),
            'phone_numbers' => array_values($request->input('phones', [])),
            'bank_accounts' => array_values($request->input('banks', [])),
            'status' => User::STATUS_PENDING,
            'role_id' => null,
            'rejection_reason' => null,
            'rejected_at' => null,
            ...$paths,
        ];

        if ($existing !== null) {
            $this->deletePrivateFiles($existing);
            $existing->update($details);
            $user = $existing;
        } else {
            $user = User::query()->create($details);
        }

        $wallets->open($user);

        return redirect()
            ->route('login')
            ->with('status', 'Your registration was sent. It stays closed until someone approves it.');
    }

    private function deletePrivateFiles(User $user): void
    {
        foreach (['profile_photo_path', 'cnic_front_path', 'cnic_back_path'] as $field) {
            $path = $user->{$field};

            if (is_string($path) && $path !== '') {
                Storage::disk('local')->delete($path);
            }
        }
    }
}
