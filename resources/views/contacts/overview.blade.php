<x-layouts.shell title="Contacts" :user="$user">
    <p class="mb-4 text-sm text-ink/70">UI preview from the old Agricart Contacts cluster. Saving comes next.</p>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($cards as $card)
            <a href="{{ route($card['route']) }}" class="rounded-2xl border border-line bg-card p-5 transition hover:border-ink/30">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-lg font-semibold">{{ $card['label'] }}</p>
                        <p class="mt-1 text-sm text-ink/70">{{ $card['hint'] }}</p>
                    </div>
                    <p class="text-3xl font-semibold tabular-nums">{{ $card['count'] }}</p>
                </div>
            </a>
        @endforeach
    </div>
</x-layouts.shell>
