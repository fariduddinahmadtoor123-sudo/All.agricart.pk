@props(['schemes'])

<div data-scheme-list class="overflow-hidden rounded-2xl border border-line bg-card">
    <table class="w-full text-left text-sm">
        <thead class="bg-soft">
            <tr>
                <th class="px-6 py-3 font-semibold">Name</th>
                <th class="px-6 py-3 font-semibold">Colors</th>
                <th class="px-6 py-3 text-right font-semibold">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($schemes as $scheme)
                <tr class="border-t border-line">
                    <td class="px-6 py-4 font-semibold">{{ $scheme->name }}</td>
                    <td class="px-6 py-4">
                        <span class="flex gap-1" aria-hidden="true">
                            @foreach ([$scheme->page, $scheme->card, $scheme->soft, $scheme->line, $scheme->primary] as $color)
                                <span class="size-3 rounded-full border border-line" style="background: {{ $color }}"></span>
                            @endforeach
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" data-window-open="color-window-{{ $scheme->id }}" class="rounded-xl border border-line bg-canvas px-3 py-2 text-sm font-semibold">Edit</button>
                            <form method="POST" action="{{ route('colors.destroy', $scheme) }}" onsubmit="return confirm('Delete this color scheme?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-xl border border-line bg-canvas px-3 py-2 text-sm font-semibold">Delete</button>
                            </form>
                            <button
                                type="button"
                                data-scheme-id="{{ $scheme->id }}"
                                data-page="{{ $scheme->page }}"
                                data-card="{{ $scheme->card }}"
                                data-soft="{{ $scheme->soft }}"
                                data-line="{{ $scheme->line }}"
                                data-primary="{{ $scheme->primary }}"
                                class="rounded-xl border border-line bg-canvas px-3 py-2 text-sm font-semibold aria-pressed:bg-primary"
                            >Apply</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-6 py-10 text-ink/70" colspan="3">No colors yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
