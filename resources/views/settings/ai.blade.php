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
            <p class="rounded-2xl bg-soft px-4 py-3 text-base font-semibold leading-7">
                {{ $configuration->displayName() }}
                ·
                @if ($configuration->isConnected())
                    Connected
                @elseif ($configuration->isPaused())
                    Paused
                @elseif ($configuration->isOutOfCredit())
                    Credits finished
                @elseif ($configuration->connection_status === 'failed')
                    Connection failed
                @else
                    Saved
                @endif
                · {{ $configuration->provider->label() }}
                · {{ $configuration->model_id }}
                @if ($configuration->last_tested_at)
                    · Last check {{ $configuration->last_tested_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                @endif
                @if ($configuration->last_test_message)
                    · {{ $configuration->last_test_message }}
                @endif
            </p>
            @php
                $answer = session('ai_answer');

                if (! filled($answer)) {
                    $answer = $logs->first(fn ($log) => $log->type === \App\Enums\AiLogType::Prompt && filled($log->response))?->response;
                }
            @endphp
            <div class="mt-4 grid items-stretch gap-3 sm:grid-cols-2">
                <form id="ai-ask-form" method="POST" action="{{ route('settings.ai.ask') }}" class="flex min-h-40 flex-col gap-2">
                    @csrf
                    <label for="ai-page-question" class="flex h-8 items-center text-sm font-semibold">Ask a test question</label>
                    <textarea id="ai-page-question" name="question" required placeholder="Ask something short." class="min-h-28 flex-1 resize-none rounded-xl border border-line bg-card px-4 py-3 font-normal">{{ old('question') }}</textarea>
                    @error('question')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                </form>
                <div class="flex min-h-40 flex-col gap-2">
                    <div class="flex h-8 items-center justify-between gap-2">
                        <p class="text-sm font-semibold">Answer</p>
                        @if (filled($answer))
                            <button type="button" data-copy-for="ai-page-answer" class="rounded-lg px-2 py-1 text-xs font-semibold hover:bg-soft" aria-label="Copy answer">Copy</button>
                        @endif
                    </div>
                    <div class="min-h-28 flex-1 rounded-xl border border-line bg-card px-4 py-3">
                        <p id="ai-page-answer" class="whitespace-pre-wrap text-sm">{{ filled($answer) ? $answer : 'No answer yet.' }}</p>
                    </div>
                </div>
            </div>
            <div class="mt-3 grid grid-cols-3 gap-3">
                <button type="submit" form="ai-ask-form" class="rounded-xl bg-primary px-4 py-3 text-sm font-semibold">Ask</button>
                <form method="POST" action="{{ route('settings.ai.test') }}">@csrf<button class="w-full rounded-xl border border-line bg-card px-4 py-3 text-sm font-semibold">Test</button></form>
                @if ($configuration->isPaused())
                    <form method="POST" action="{{ route('settings.ai.resume') }}">@csrf<button class="w-full rounded-xl border border-line bg-primary px-4 py-3 text-sm font-semibold">Resume</button></form>
                @else
                    <form method="POST" action="{{ route('settings.ai.pause') }}">@csrf<button class="w-full rounded-xl border border-line bg-card px-4 py-3 text-sm font-semibold">Pause</button></form>
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

    @push('scripts')
        <script>
            document.querySelectorAll('[data-copy-for]').forEach((button) => {
                button.addEventListener('click', async () => {
                    const node = document.getElementById(button.getAttribute('data-copy-for'));
                    const text = node?.textContent?.trim() ?? '';

                    if (text === '') {
                        return;
                    }

                    await navigator.clipboard.writeText(text);
                    const previous = button.textContent;
                    button.textContent = 'Copied';
                    window.setTimeout(() => {
                        button.textContent = previous;
                    }, 1200);
                });
            });
        </script>
    @endpush
</x-layouts.shell>
