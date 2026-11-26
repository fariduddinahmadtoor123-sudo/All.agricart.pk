<form method="POST" action="{{ route('settings.ai.store') }}" class="flex h-full flex-col gap-5 p-6">
    @csrf
    <input type="hidden" name="_form" value="ai">

    <p class="text-sm text-ink/70">Paste your OpenRouter key, load models, pick one, then connect. Desk uses this for Contacts and Forex.</p>

    <div class="grid gap-4 sm:grid-cols-2">
        <label class="grid gap-1 text-sm font-semibold">Provider
            <select name="provider" class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">
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
                    class="w-full rounded-xl border border-line bg-canvas py-3 pe-12 ps-4 font-normal"
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

    <div class="flex flex-wrap items-center gap-2">
        <button
            type="submit"
            formaction="{{ route('settings.ai.models') }}"
            class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold"
        >Load models</button>
        @if ($models !== [])
            <span class="text-sm text-ink/60">{{ count($models) }} models — pick one</span>
        @endif
    </div>

    <label class="grid gap-1 text-sm font-semibold">Model
        @if ($models !== [])
            <input type="search" id="ai-model-filter" placeholder="Search models…" class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">
            <select name="model_id" id="ai-model-select" required size="8" class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">
                @foreach ($models as $model)
                    <option
                        value="{{ $model['id'] }}"
                        data-name="{{ $model['name'] }}"
                        @selected(old('model_id', $configuration?->model_id) === $model['id'])
                    >{{ $model['name'] }} — {{ $model['id'] }}</option>
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

    <div class="mt-auto flex flex-wrap gap-2 pt-2">
        <button type="submit" class="rounded-xl bg-primary px-5 py-3 text-sm font-semibold">
            {{ $configuration ? 'Save & reconnect' : 'Connect' }}
        </button>
    </div>
</form>

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
