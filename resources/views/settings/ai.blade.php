<x-layouts.shell title="AI settings" :user="$user">
    @if (session('status'))
        <p class="mb-4 text-sm">{{ session('status') }}</p>
    @endif

    <div class="mb-6 rounded-2xl border border-line bg-card p-5">
        <p class="text-sm text-ink/70">Desk talks to AI through OpenRouter. Create a key on openrouter.ai, load models here, pick one, then Save & test. The same connection will power Contacts help and Forex assist.</p>

        @if ($configuration)
            <div class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
                <p><span class="font-semibold">Status:</span> <span class="capitalize">{{ $configuration->connection_status }}</span></p>
                <p><span class="font-semibold">Model:</span> {{ $configuration->displayName() }}</p>
                <p><span class="font-semibold">Key:</span> {{ $configuration->maskedApiKey() }}</p>
                <p><span class="font-semibold">Last test:</span> {{ $configuration->last_tested_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?: '—' }}</p>
            </div>
            @if ($configuration->last_test_message)
                <p class="mt-3 text-sm text-ink/70">{{ $configuration->last_test_message }}</p>
            @endif
            <div class="mt-4 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('settings.ai.test') }}">@csrf<button class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Test connection</button></form>
                @if ($configuration->isPaused())
                    <form method="POST" action="{{ route('settings.ai.resume') }}">@csrf<button class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Resume</button></form>
                @else
                    <form method="POST" action="{{ route('settings.ai.pause') }}">@csrf<button class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Pause</button></form>
                @endif
                <a href="{{ route('settings.ai-logs') }}" class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">AI logs</a>
            </div>
        @endif
    </div>

    <form method="POST" action="{{ route('settings.ai.models') }}" class="mb-6 rounded-2xl border border-line bg-card p-5">
        @csrf
        <h2 class="text-sm font-semibold">1. Load models</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <label class="grid gap-1 text-sm font-semibold">Provider
                <select name="provider" class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">
                    @foreach ($providers as $provider)
                        <option value="{{ $provider['value'] }}" @selected(old('provider', 'openrouter') === $provider['value']) @disabled(! $provider['enabled'])>
                            {{ $provider['label'] }}@unless ($provider['enabled']) (soon)@endunless
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-1 text-sm font-semibold">OpenRouter API key
                <input name="api_key" type="password" value="{{ old('api_key') }}" autocomplete="off" placeholder="{{ $configuration?->hasUsableApiKey() ? 'Leave blank to keep saved key' : 'sk-or-…' }}" class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">
                @error('api_key')<span class="font-normal text-red-700">{{ $message }}</span>@enderror
            </label>
        </div>
        <button type="submit" class="mt-4 rounded-xl bg-primary px-4 py-2 text-sm font-semibold">Load models</button>
    </form>

    <form method="POST" action="{{ route('settings.ai.store') }}" class="rounded-2xl border border-line bg-card p-5">
        @csrf
        <h2 class="text-sm font-semibold">2. Choose model & save</h2>
        <input type="hidden" name="provider" value="{{ old('provider', 'openrouter') }}">
        <input type="hidden" name="api_key" value="{{ old('api_key') }}">

        <div class="mt-4 grid gap-4">
            <label class="grid gap-1 text-sm font-semibold">Model
                @if ($models !== [])
                    <input type="search" id="ai-model-filter" placeholder="Filter models…" class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">
                    <select name="model_id" id="ai-model-select" required size="12" class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">
                        @foreach ($models as $model)
                            <option
                                value="{{ $model['id'] }}"
                                data-name="{{ $model['name'] }}"
                                @selected(old('model_id', $configuration?->model_id) === $model['id'])
                            >{{ $model['name'] }} ({{ $model['id'] }})</option>
                        @endforeach
                    </select>
                @else
                    <select name="model_id" required class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">
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
        </div>

        <button type="submit" class="mt-4 rounded-xl bg-primary px-4 py-2 text-sm font-semibold">Save & test</button>
    </form>

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
