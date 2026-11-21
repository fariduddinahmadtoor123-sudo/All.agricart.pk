@props(['number', 'name'])

<p {{ $attributes->merge(['class' => 'flex shrink-0 items-center gap-2 text-xs font-bold tracking-wide uppercase']) }}>
    <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-ink text-[11px] text-canvas">{{ $number }}</span>
    <span>{{ $name }}</span>
</p>
