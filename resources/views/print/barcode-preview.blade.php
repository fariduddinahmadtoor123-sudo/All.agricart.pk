<x-print.layout :profile="$profile" title="Barcode preview">
    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0.4rem;text-align:center;min-height:100%;">
        <div style="width:100%;max-width:100%;overflow:hidden;">
            {!! $barcodeSvg !!}
        </div>
        <div style="font-size:0.85rem;letter-spacing:0.04em;">{{ $code }}</div>
        <div style="font-size:0.75rem;opacity:0.7;">{{ $profile->localizedName() }} · {{ $profile->dimensionsLabel() }}</div>
    </div>
</x-print.layout>
