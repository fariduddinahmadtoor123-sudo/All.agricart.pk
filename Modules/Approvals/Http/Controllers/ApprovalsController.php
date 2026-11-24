<?php

namespace Modules\Approvals\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Approvals\Http\Requests\DecideRegistrationRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApprovalsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isSuperAdmin(), 403);

        return view('approvals', [
            'user' => $user,
            'roles' => Role::query()->orderBy('name')->get(),
            'pending' => User::query()->where('status', User::STATUS_PENDING)->latest()->get(),
            'history' => User::query()
                ->where(function ($query): void {
                    $query->where('status', User::STATUS_REJECTED)
                        ->orWhere(function ($query): void {
                            $query->where('status', User::STATUS_ACTIVE)->whereNotNull('phone_numbers');
                        });
                })
                ->latest('updated_at')
                ->get(),
        ]);
    }

    public function approve(DecideRegistrationRequest $request, User $registration): RedirectResponse
    {
        if ($registration->status !== User::STATUS_PENDING) {
            return back()->withErrors(['registration' => 'This person is not waiting.']);
        }

        $registration->update([
            'role_id' => $request->integer('role_id'),
            'status' => User::STATUS_ACTIVE,
            'rejection_reason' => null,
            'rejected_at' => null,
        ]);

        return redirect()->route('approvals')->with('status', $registration->name.' is approved.');
    }

    public function reject(DecideRegistrationRequest $request, User $registration): RedirectResponse
    {
        if ($registration->status !== User::STATUS_PENDING) {
            return back()->withErrors(['registration' => 'This person is not waiting.']);
        }

        $registration->update([
            'status' => User::STATUS_REJECTED,
            'role_id' => null,
            'rejection_reason' => $request->string('reason')->toString(),
            'rejected_at' => now(),
        ]);

        return redirect()->route('approvals')->with('status', $registration->name.' was rejected.');
    }

    public function file(Request $request, User $registration, string $kind): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isSuperAdmin(), 403);

        $path = match ($kind) {
            'profile' => $registration->profile_photo_path,
            'cnic-front' => $registration->cnic_front_path,
            'cnic-back' => $registration->cnic_back_path,
            default => null,
        };

        abort_unless(is_string($path) && $path !== '' && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
