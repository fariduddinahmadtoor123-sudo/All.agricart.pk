<form method="POST" action="{{ route('settings.ai.store') }}" class="flex h-full flex-col gap-5 p-6">
    @csrf
    <input type="hidden" name="_form" value="ai">

    <p class="text-sm text-ink/70">Paste the OpenRouter key, load models, keep the cheap pick or choose another, then connect.</p>

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
                    value=""
                    autocomplete="off"
                    placeholder="{{ $configuration?->hasUsableApiKey() ? 'Saved. Leave blank to keep it.' : 'sk-or-…' }}"
                    class="w-full rounded-xl border border-line bg-canvas py-3 pr-12 pl-4 font-normal"
                >
                <x-password-eye input="ai_api_key" label="Show API key" />
            </div>
            @error('api_key')<span class="font-normal text-red-700">{{ $message }}</span>@enderror
        </label>
    </div>

    @php
        $selectedModelId = old('model_id', $configuration?->model_id);
        $selectedModelName = old('model_name', $configuration?->model_name);
        $offered = collect($models);
        $cheapModels = collect($recommended ?? [])->filter(
            fn (array $model): bool => $offered->contains(fn (array $loaded): bool => $loaded['id'] === $model['id']),
        )->values();
    @endphp

    <div class="flex flex-wrap items-center gap-2">
        <button
            type="submit"
            formnovalidate
            formaction="{{ route('settings.ai.models') }}"
            class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold"
        >Load models</button>
        @if ($models !== [])
            <span class="text-sm text-ink/60">{{ count($models) }} models from OpenRouter</span>
        @endif
    </div>

    <div class="grid gap-1 text-sm font-semibold">
        <span>Selected model</span>
        <p id="ai-selected-line" class="rounded-xl bg-soft px-3 py-2 font-normal" @if (! filled($selectedModelId)) hidden @endif>
            <span id="ai-selected-name">{{ $selectedModelName ?: $selectedModelId }}</span>
        </p>
        @unless (filled($selectedModelId))
            <p id="ai-selected-empty" class="font-normal text-ink/60">Nothing selected yet. Load models and Desk will pick the cheap one.</p>
        @endunless
        @error('model_id')<span class="font-normal text-red-700">{{ $message }}</span>@enderror
    </div>
    <input type="hidden" name="model_id" id="ai-model-id" value="{{ $selectedModelId }}">
    <input type="hidden" name="model_name" id="ai-model-name" value="{{ $selectedModelName }}">

    @if ($models === [])
        <div class="rounded-xl border border-line bg-canvas p-3 text-sm font-normal">
            <p class="font-semibold">Cheap models to keep</p>
            <ul class="mt-2 grid gap-1 text-ink/80">
                @foreach ($recommended ?? [] as $model)
                    <li>{{ $model['name'] }} — {{ $model['note'] }}</li>
                @endforeach
            </ul>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid content-start gap-2">
                <p class="text-sm font-semibold">Cheap models</p>
                @forelse ($cheapModels as $model)
                    <button
                        type="button"
                        data-model-pick
                        data-model-id="{{ $model['id'] }}"
                        data-model-name="{{ $model['name'] }}"
                        @if ($model['id'] === ($preferredModelId ?? '')) data-model-preferred @endif
                        class="rounded-xl border border-line bg-canvas px-3 py-2 text-left font-normal hover:bg-soft"
                    >
                        <span class="block text-sm font-semibold">{{ $model['name'] }}</span>
                        <span class="mt-1 block text-xs text-ink/60">{{ $model['note'] }}</span>
                    </button>
                @empty
                    <p class="text-sm font-normal text-ink/60">These cheap picks are not in this key’s list. Choose from all models.</p>
                @endforelse
            </div>
            <div class="grid content-start gap-2">
                <label class="grid gap-1 text-sm font-semibold" for="ai-model-filter">All models
                    <input type="search" id="ai-model-filter" placeholder="Search models…" class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">
                </label>
                <div id="ai-model-list" class="grid gap-1">
                    @foreach ($models as $model)
                        <button
                            type="button"
                            data-model-pick
                            data-model-id="{{ $model['id'] }}"
                            data-model-name="{{ $model['name'] }}"
                            data-model-search="{{ strtolower($model['name'].' '.$model['id']) }}"
                            class="rounded-xl border border-line bg-canvas px-3 py-2 text-left font-normal hover:bg-soft"
                        >
                            <span class="block text-sm font-semibold">{{ $model['name'] }}</span>
                            <span class="block text-xs text-ink/60">{{ $model['id'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="grid items-start gap-3 sm:grid-cols-2">
        <label class="grid gap-1 text-sm font-semibold" for="ai-question">Test question
            <textarea id="ai-question" name="question" rows="3" placeholder="Ask a short question to see a real reply." class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">{{ old('question') }}</textarea>
            @error('question')<span class="font-normal text-red-700">{{ $message }}</span>@enderror
        </label>
        @if (session('ai_draft_answer'))
            <div class="rounded-xl border border-line bg-canvas p-4">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <p class="text-sm font-semibold">Answer</p>
                    <button type="button" data-copy-for="ai-draft-answer" class="rounded-lg px-2 py-1 text-xs font-semibold hover:bg-soft" aria-label="Copy answer">Copy</button>
                </div>
                <p id="ai-draft-answer" class="whitespace-pre-wrap text-sm font-normal">{{ session('ai_draft_answer') }}</p>
            </div>
        @endif
    </div>

    <div class="mt-auto flex flex-wrap gap-2 pt-2">
        <button
            type="submit"
            formnovalidate
            formaction="{{ route('settings.ai.ask-draft') }}"
            class="rounded-xl border border-line bg-canvas px-5 py-3 text-sm font-semibold"
        >Ask</button>
        <button type="submit" class="rounded-xl bg-primary px-5 py-3 text-sm font-semibold">
            {{ $configuration ? 'Save & reconnect' : 'Connect' }}
        </button>
    </div>
</form>

@include('auth.partials.password-toggle')

<script>
    (() => {
        const key = document.getElementById('ai_api_key');
        if (key) {
            const clearKey = () => {
                if (document.activeElement !== key) {
                    key.value = '';
                }
            };
            clearKey();
            window.setTimeout(clearKey, 400);
        }

        const idInput = document.getElementById('ai-model-id');
        const nameInput = document.getElementById('ai-model-name');
        const line = document.getElementById('ai-selected-line');
        const lineName = document.getElementById('ai-selected-name');
        const empty = document.getElementById('ai-selected-empty');
        const filter = document.getElementById('ai-model-filter');
        const picks = Array.from(document.querySelectorAll('[data-model-pick]'));

        const mark = (id) => {
            picks.forEach((button) => {
                const on = button.dataset.modelId === id;
                button.classList.toggle('bg-primary', on);
                button.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
        };

        const choose = (id, name) => {
            if (! idInput || ! name) {
                return;
            }

            idInput.value = id;
            nameInput.value = name;
            if (lineName) {
                lineName.textContent = name;
            }
            if (line) {
                line.hidden = false;
            }
            if (empty) {
                empty.hidden = true;
            }
            mark(id);
        };

        picks.forEach((button) => {
            button.addEventListener('click', () => choose(button.dataset.modelId, button.dataset.modelName));
        });

        if (idInput?.value) {
            mark(idInput.value);
        } else {
            document.querySelector('[data-model-preferred]')?.click();
        }

        filter?.addEventListener('input', () => {
            const needle = filter.value.trim().toLowerCase();
            document.querySelectorAll('#ai-model-list [data-model-pick]').forEach((button) => {
                const hay = button.dataset.modelSearch || '';
                button.hidden = needle !== '' && ! hay.includes(needle);
            });
        });
    })();
</script>
