<x-layouts.shell title="AI Connection" :user="$user">
    <x-slot:panel>
        <button type="button" data-window-open="ai-connection-window" class="rounded-xl bg-card px-4 py-2 text-sm font-semibold">
            {{ $configuration ? 'Change connection' : 'Connect AI' }}
        </button>
    </x-slot:panel>

    @if (session('status'))
        <p class="mb-4 text-sm">{{ session('status') }}</p>
    @endif

    <div class="mb-8">
        @if ($configuration)
            <p class="text-lg font-semibold">{{ $configuration->displayName() }}</p>
            <p class="mt-1 text-sm text-ink/70">
                @if ($configuration->isConnected())
                    Connected
                @elseif ($configuration->isPaused())
                    Paused
                @elseif ($configuration->connection_status === 'failed')
                    Connection failed
                @else
                    Saved — not tested
                @endif
                · {{ $configuration->provider->label() }}
                · key {{ $configuration->maskedApiKey() }}
            </p>
            @if ($configuration->model_id && $configuration->model_name && $configuration->model_name !== $configuration->model_id)
                <p class="mt-1 text-xs text-ink/50">{{ $configuration->model_id }}</p>
            @endif
            @if ($configuration->last_tested_at)
                <p class="mt-1 text-xs text-ink/50">Last check {{ $configuration->last_tested_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</p>
            @endif
            @if ($configuration->last_test_message)
                <p class="mt-2 max-w-2xl text-sm text-ink/70">{{ $configuration->last_test_message }}</p>
            @endif
            <div class="mt-4 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('settings.ai.test') }}">@csrf<button class="rounded-xl border border-line bg-card px-4 py-2 text-sm font-semibold">Test</button></form>
                @if ($configuration->isPaused())
                    <form method="POST" action="{{ route('settings.ai.resume') }}">@csrf<button class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold">Resume</button></form>
                @else
                    <form method="POST" action="{{ route('settings.ai.pause') }}">@csrf<button class="rounded-xl border border-line bg-card px-4 py-2 text-sm font-semibold">Pause</button></form>
                @endif
            </div>
        @else
            <p class="text-lg font-semibold">No model connected</p>
            <p class="mt-1 text-sm text-ink/70">Use <span class="font-semibold">Connect AI</span> above to add your OpenRouter key and pick a model.</p>
        @endif
    </div>

    <section id="activity" class="border-t border-line pt-8">
        <h2 class="text-sm font-semibold">Activity</h2>
        <p class="mt-1 mb-4 text-sm text-ink/70">Tests, model loads, and AI replies Desk made.</p>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[40rem] text-left text-sm">
                <thead>
                    <tr class="border-b border-line text-ink/60">
                        <th class="pb-3 pe-4 font-semibold">When</th>
                        <th class="pb-3 pe-4 font-semibold">Type</th>
                        <th class="pb-3 pe-4 font-semibold">Status</th>
                        <th class="pb-3 pe-4 font-semibold">Model</th>
                        <th class="pb-3 font-semibold">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="border-b border-line align-top">
                            <td class="py-3 pe-4 tabular-nums whitespace-nowrap">{{ $log->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                            <td class="py-3 pe-4">{{ $log->type->label() }}</td>
                            <td class="py-3 pe-4">
                                <span class="{{ $log->isSuccess() ? 'text-ink' : 'text-red-700' }}">{{ $log->status->label() }}</span>
                                @if ($log->duration_ms)<span class="ms-1 text-xs text-ink/50">{{ $log->duration_ms }}ms</span>@endif
                            </td>
                            <td class="py-3 pe-4">{{ $log->modelLabel() }}</td>
                            <td class="py-3">
                                <p class="max-w-xl whitespace-pre-wrap text-ink/80">{{ $log->error_message ?: \Illuminate\Support\Str::limit((string) $log->response, 160) }}</p>
                                @if ($log->source !== 'system')
                                    <p class="mt-1 text-xs text-ink/50">{{ $log->source }}</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-8 text-ink/70" colspan="5">No activity yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @push('windows')
        <x-desk-window
            id="ai-connection-window"
            :title="$configuration ? 'Change AI connection' : 'Connect AI'"
            :open="$errors->any() || old('_form') === 'ai' || (bool) session('ai_window')"
        >
            @include('settings.ai.partials.form')
        </x-desk-window>
    @endpush
</x-layouts.shell>
