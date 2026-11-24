<x-layouts.shell title="Approvals" :user="$user">
    @if (session('status'))
        <p class="mb-3 text-sm">{{ session('status') }}</p>
    @endif

    <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-line bg-card" data-arrow-pane>
        <button type="button" class="side-arrow w-full border-b border-line bg-soft" data-scroll-up hidden aria-label="Approvals above">
            <svg viewBox="0 0 16 16" class="mx-auto h-4 w-4" aria-hidden="true"><path d="M3.5 10.5 8 6l4.5 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
        </button>
        <div class="arrow-scroll min-h-0 flex-1" data-arrow-scroll>
            <table class="w-full text-left text-sm">
                <thead class="bg-soft">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Name</th>
                        <th class="px-4 py-3 font-semibold">Phone</th>
                        <th class="px-4 py-3 font-semibold">Bank</th>
                        <th class="px-4 py-3 font-semibold">Papers</th>
                        <th class="px-4 py-3 font-semibold">Decision</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pending as $person)
                        <tr class="border-t border-line align-top">
                            <td class="px-4 py-3">
                                <p class="font-semibold">{{ $person->name }}</p>
                                <p class="text-ink/70">{{ $person->name_ur }}</p>
                                <p class="text-ink/70">{{ $person->email }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $person->phone_numbers[0]['phone_number'] ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $person->bank_accounts[0]['bank_name'] ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <a class="block underline" href="{{ route('approvals.file', [$person, 'profile']) }}">Photo</a>
                                <a class="block underline" href="{{ route('approvals.file', [$person, 'cnic-front']) }}">Card front</a>
                                <a class="block underline" href="{{ route('approvals.file', [$person, 'cnic-back']) }}">Card back</a>
                            </td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('approvals.approve', $person) }}" class="grid gap-2">
                                    @csrf
                                    <select name="role_id" required class="rounded-xl border border-line bg-canvas px-3 py-2">
                                        <option value="">Role</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="rounded-xl bg-primary px-3 py-2 text-sm font-semibold">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('approvals.reject', $person) }}" class="mt-2 grid gap-2">
                                    @csrf
                                    <input name="reason" required maxlength="255" placeholder="Reason" class="rounded-xl border border-line bg-canvas px-3 py-2">
                                    <button type="submit" class="rounded-xl border border-line bg-canvas px-3 py-2 text-sm font-semibold">Reject</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-10 text-ink/70" colspan="5">No one is waiting.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <h2 class="border-t border-line px-4 py-3 text-sm font-semibold">History</h2>
            <table class="w-full text-left text-sm">
                <thead class="bg-soft">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Name</th>
                        <th class="px-4 py-3 font-semibold">Result</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($history as $person)
                        <tr class="border-t border-line">
                            <td class="px-4 py-3 font-semibold">{{ $person->name }}</td>
                            <td class="px-4 py-3">
                                @if ($person->status === \App\Models\User::STATUS_REJECTED)
                                    Rejected. {{ $person->rejection_reason }}
                                @else
                                    Approved
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-6 text-ink/70" colspan="2">No decisions yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <button type="button" class="side-arrow w-full border-t border-line bg-soft" data-scroll-down hidden aria-label="Approvals below">
            <svg viewBox="0 0 16 16" class="mx-auto h-4 w-4" aria-hidden="true"><path d="M3.5 5.5 8 10l4.5-4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
        </button>
    </div>
</x-layouts.shell>
