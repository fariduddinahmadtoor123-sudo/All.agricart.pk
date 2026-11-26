<x-layouts.shell title="AI Connection" :user="$user">
    @if (session('status'))
        <p class="mb-4 text-sm">{{ session('status') }}</p>
    @endif

    <p class="mb-6 max-w-2xl text-sm text-ink/70">Connect Desk to OpenRouter once. Contacts help and Forex assist both use this connection.</p>

    @if ($configuration)
        <div class="mb-6 border-b border-line pb-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold">
                        @if ($configuration->isConnected())
                            Connected
                        @elseif ($configuration->isPaused())
                            Paused
                        @elseif ($configuration->connection_status === 'failed')
                            Connection failed
                        @else
                            Not tested yet
                        @endif
                    </p>
                    <p class="mt-1 text-sm text-ink/70">{{ $configuration->displayName() }} · key {{ $configuration->maskedApiKey() }}</p>
                    @if ($configuration->last_tested_at)
                        <p class="mt-1 text-xs text-ink/60">Last check {{ $configuration->last_tested_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</p>
                    @endif
                    @if ($configuration->last_test_message)
                        <p class="mt-2 max-w-xl text-sm text-ink/70">{{ $configuration->last_test_message }}</p>
                    @endif
                </div>
                <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('settings.ai.test') }}">@csrf<button class="rounded-xl border border-line bg-card px-4 py-2 text-sm font-semibold">Test</button></form>
                    @if ($configuration->isPaused())
                        <form method="POST" action="{{ route('settings.ai.resume') }}">@csrf<button class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold">Resume</button></form>
                    @else
                        <form method="POST" action="{{ route('settings.ai.pause') }}">@csrf<button class="rounded-xl border border-line bg-card px-4 py-2 text-sm font-semibold">Pause</button></form>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('settings.ai.store') }}" class="mb-10 max-w-3xl">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="grid gap-1 text-sm font-semibold">Provider
                <select name="provider" class="rounded-xl border border-line bg-card px-4 py-3 font-normal">
                    @foreach ($providers as $provider)
                        <option value="{{ $provider['value'] }}" @selected(old('provider', $configuration?->provider?->value ?? 'openrouter') === $provider['value']) @disabled(! $provider['enabled'])>
                            {{ $provider['label'] }}@unless ($provider['enabled']) (soon)@endunless
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="grid gap-1 text-sm font-semibold" for="ai_api_key">API key
                <div class="relative">
                    <input
                        id="ai_api_key"
                        name="api_key"
                        type="password"
                        value="{{ old('api_key') }}"
                        autocomplete="off"
                        placeholder="{{ $configuration?->hasUsableApiKey() ? 'Leave blank to keep saved key' : 'sk-or-…' }}"
                        class="w-full rounded-xl border border-line bg-card py-3 pe-12 ps-4 font-normal"
                    >
                    <button type="button" data-password-toggle="ai_api_key" class="absolute inset-y-0 end-0 grid w-12 place-items-center text-ink/70 hover:text-ink" aria-label="Show API key" aria-pressed="false">
                        <svg data-eye-open viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true">
                            <path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"></path>
                            <circle cx="8" cy="8" r="2" fill="none" stroke="currentColor" stroke-width="1.4"></circle>
                        </svg>
                        <svg data-eye-shut viewBox="0 0 16 16" class="h-4 w-4" hidden aria-hidden="true">
                            <path d="M2 2.5 14 13.5" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"></path>
                            <path d="M6.2 4.1A7.2 7.2 0 0 1 8 3.5c4 0 6.5 4.5 6.5 4.5a11 11 0 0 1-2.1 2.3M4.2 5.7A11 11 0 0 0 1.5 8S4 12.5 8 12.5c.7 0 1.4-.1 2-.3" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </button>
                </div>
                @error('api_key')<span class="font-normal text-red-700">{{ $message }}</span>@enderror
            </label>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <button
                type="submit"
                formaction="{{ route('settings.ai.models') }}"
                class="rounded-xl border border-line bg-card px-4 py-2 text-sm font-semibold"
            >Load models</button>
            @if ($models !== [])
                <p class="self-center text-sm text-ink/60">{{ count($models) }} models ready — pick one below</p>
            @endif
        </div>

        <label class="mt-6 grid gap-1 text-sm font-semibold">Model
            @if ($models !== [])
                <input type="search" id="ai-model-filter" placeholder="Search models…" class="rounded-xl border border-line bg-card px-4 py-3 font-normal">
                <select name="model_id" id="ai-model-select" required size="10" class="rounded-xl border border-line bg-card px-4 py-3 font-normal">
                    @foreach ($models as $model)
                        <option
                            value="{{ $model['id'] }}"
                            data-name="{{ $model['name'] }}"
                            @selected(old('model_id', $configuration?->model_id) === $model['id'])
                        >{{ $model['name'] }} — {{ $model['id'] }}</option>
                    @endforeach
                </select>
            @else
                <select name="model_id" required class="rounded-xl border border-line bg-card px-4 py-3 font-normal">
                    @if ($configuration?->model_id)
                        <option value="{{ $configuration->model_id }}" selected>{{ $configuration->displayName() }}</option>
                    @else
                        <option value="">Load models first</option>
                    @endif
                </select>
            @endif
            @error('model_id')<span class="font-normal text-red-700">{{ $message }}</span>@enderror
        </label>
        <input type="hidden" name="model_name" id="ai-model-name" value="{{ old('model_name', $configuration?->model_name) }}">

        <button type="submit" class="mt-6 rounded-xl bg-primary px-5 py-3 text-sm font-semibold">
            {{ $configuration ? 'Save & reconnect' : 'Connect' }}
        </button>
    </form>

    <section id="activity" class="border-t border-line pt-8">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
            <div>
                <h2 class="text-sm font-semibold">Recent activity</h2>
                <p class="mt-1 text-sm text-ink/70">Connection tests, model loads, and AI replies Desk made.</p>
            </div>
        </div>

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
                            <td class="py-8 text-ink/70" colspan="5">No activity yet. Connect or test to see logs here.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @include('auth.partials.password-toggle')

    <script>
        (() => {
            const filter = document.getElementById('ai-model-filter');
            const select = document.getElementById('ai-model-select');
            const nameInput = document.getElementById('ai-model-name');

            if (! select) {
                return;
            }

            const syncName = () => {
                const option = select.selectedOptions[0];
                if (nameInput && option) {
                    nameInput.value = option.dataset.name || option.textContent || '';
                }
            };

            select.addEventListener('change', syncName);
            syncName();

            filter?.addEventListener('input', () => {
                const needle = filter.value.trim().toLowerCase();
                Array.from(select.options).forEach((option) => {
                    const hay = `${option.value} ${option.textContent}`.toLowerCase();
                    option.hidden = needle !== '' && ! hay.includes(needle);
                });
            });
        })();
    </script>
</x-layouts.shell>
