<div {{ $attributes->class(['flex flex-wrap gap-3']) }} role="group" aria-label="Corners">
    @foreach ([
        ['value' => 'square', 'label' => 'Square', 'radius' => '0'],
        ['value' => 'soft', 'label' => 'Soft', 'radius' => '4px'],
        ['value' => 'round', 'label' => 'Round', 'radius' => '12px'],
        ['value' => 'smooth', 'label' => 'Smooth', 'radius' => '20px'],
        ['value' => 'pill', 'label' => 'Pill', 'radius' => '999px'],
    ] as $shape)
        <button
            type="button"
            data-corner="{{ $shape['value'] }}"
            class="flex w-28 flex-col items-center gap-2 border border-line bg-canvas px-3 py-3 text-sm font-semibold aria-pressed:bg-primary"
            style="border-radius: {{ $shape['radius'] }}"
            @if ($shape['value'] === 'round') aria-pressed="true" @endif
        >
            <span class="block h-8 w-full border border-line bg-card" style="border-radius: {{ $shape['radius'] }}"></span>
            {{ $shape['label'] }}
        </button>
    @endforeach
</div>
