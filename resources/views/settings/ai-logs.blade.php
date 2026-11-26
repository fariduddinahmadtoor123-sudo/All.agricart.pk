<x-layouts.shell title="AI logs" :user="$user">
    <div class="overflow-hidden rounded-2xl border border-line bg-card">
        <table class="w-full text-left text-sm">
            <thead class="bg-soft">
                <tr>
                    <th class="px-4 py-3 font-semibold sm:px-6">When</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">Type</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">Status</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">Model</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">Source</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr class="border-t border-line align-top">
                        <td class="px-4 py-4 tabular-nums sm:px-6">{{ $log->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}</td>
                        <td class="px-4 py-4 sm:px-6">{{ $log->type->label() }}</td>
                        <td class="px-4 py-4 sm:px-6">
                            <span class="rounded-lg px-2 py-1 text-xs font-semibold {{ $log->isSuccess() ? 'bg-soft' : 'bg-red-100 text-red-800' }}">{{ $log->status->label() }}</span>
                            @if ($log->duration_ms)<span class="ml-2 text-xs text-ink/60">{{ $log->duration_ms }}ms</span>@endif
                        </td>
                        <td class="px-4 py-4 sm:px-6">{{ $log->modelLabel() }}</td>
                        <td class="px-4 py-4 sm:px-6">{{ $log->source }}</td>
                        <td class="px-4 py-4 sm:px-6">
                            <p class="max-w-xl whitespace-pre-wrap text-ink/80">{{ $log->error_message ?: \Illuminate\Support\Str::limit((string) $log->response, 180) }}</p>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-10 text-ink/70 sm:px-6" colspan="6">No AI logs yet. Save or test a connection first.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.shell>
