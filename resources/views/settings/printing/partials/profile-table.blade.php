@php($barcode = $barcode ?? false)

<section class="mb-8" aria-label="{{ $heading }}">
    <h2 class="mb-3 text-sm font-semibold tracking-wide">{{ $heading }}</h2>
    <div class="overflow-hidden rounded-2xl border border-line bg-card">
        <table class="w-full text-left text-sm">
            <thead class="bg-soft">
                <tr>
                    <th class="px-4 py-3 font-semibold sm:px-6">Name</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">Size</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">Orientation</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">Default</th>
                    <th class="px-4 py-3 text-right font-semibold sm:px-6">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($profiles as $profile)
                    <tr class="border-t border-line">
                        <td class="px-4 py-4 align-top sm:px-6">
                            <p class="font-semibold">{{ $profile->localizedName() }}</p>
                            <p class="mt-1 text-xs text-ink/60">{{ $profile->printer_hint ?: $profile->key }}@if ($profile->label_kind) · {{ $profile->label_kind }}@endif</p>
                        </td>
                        <td class="px-4 py-4 align-top tabular-nums sm:px-6">{{ $profile->dimensionsLabel() }}</td>
                        <td class="px-4 py-4 align-top capitalize sm:px-6">{{ $profile->orientation }}</td>
                        <td class="px-4 py-4 align-top sm:px-6">
                            @if ($profile->is_default)
                                <span class="rounded-lg bg-soft px-2 py-1 text-xs font-semibold">Default</span>
                            @else
                                <form method="POST" action="{{ route('settings.printing.default', $profile) }}">
                                    @csrf
                                    <button type="submit" class="text-sm font-semibold underline underline-offset-4">Make default</button>
                                </form>
                            @endif
                        </td>
                        <td class="px-4 py-4 align-top sm:px-6">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                <a href="{{ route('settings.printing.preview', $profile) }}" target="_blank" rel="noopener" class="rounded-xl border border-line bg-canvas px-3 py-2 text-sm font-semibold">Preview</a>
                                @if ($barcode)
                                    <a href="{{ route('settings.printing.barcode', $profile) }}" target="_blank" rel="noopener" class="rounded-xl border border-line bg-canvas px-3 py-2 text-sm font-semibold">Barcode</a>
                                @endif
                                <button type="button" data-window-open="print-profile-window-{{ $profile->id }}" class="rounded-xl border border-line bg-canvas px-3 py-2 text-sm font-semibold">Edit</button>
                                @unless ($profile->is_system)
                                    <form method="POST" action="{{ route('settings.printing.destroy', $profile) }}" onsubmit="return confirm('Delete this print size?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-xl border border-line bg-canvas px-3 py-2 text-sm font-semibold">Delete</button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-10 text-ink/70 sm:px-6" colspan="5">{{ $empty }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
