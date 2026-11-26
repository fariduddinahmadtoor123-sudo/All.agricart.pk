@props(['profile', 'title' => 'Desk print'])

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }}</title>
        <style>
            @page {
                size: {{ $profile->pageSizeCss() }};
                margin: {{ rtrim(rtrim(number_format((float) $profile->margin_mm, 2, '.', ''), '0'), '.') }}mm;
            }

            :root {
                color-scheme: light;
            }

            * { box-sizing: border-box; }

            body {
                margin: 0;
                font-family: Arial, Helvetica, sans-serif;
                color: #1f1f1f;
                background: #fff;
            }

            .sheet {
                width: {{ rtrim(rtrim(number_format((float) $profile->width_mm, 2, '.', ''), '0'), '.') }}mm;
                @if ($profile->height_mm !== null)
                    min-height: {{ rtrim(rtrim(number_format((float) $profile->height_mm, 2, '.', ''), '0'), '.') }}mm;
                @endif
                padding: {{ rtrim(rtrim(number_format((float) $profile->margin_mm, 2, '.', ''), '0'), '.') }}mm;
                margin: 0 auto;
            }

            .toolbar {
                position: sticky;
                top: 0;
                z-index: 10;
                display: flex;
                gap: 0.5rem;
                align-items: center;
                justify-content: space-between;
                padding: 0.75rem 1rem;
                border-bottom: 1px solid #ddd;
                background: #f7f7f7;
            }

            .toolbar button,
            .toolbar a {
                appearance: none;
                border: 1px solid #ccc;
                background: #fff;
                border-radius: 0.5rem;
                padding: 0.45rem 0.8rem;
                font: inherit;
                text-decoration: none;
                color: inherit;
                cursor: pointer;
            }

            @media print {
                .toolbar { display: none !important; }
                .sheet { margin: 0; box-shadow: none; }
            }
        </style>
    </head>
    <body>
        <div class="toolbar">
            <div>
                <strong>{{ $profile->localizedName() }}</strong>
                <span> · {{ $profile->dimensionsLabel() }} · {{ $profile->orientation }}</span>
            </div>
            <div style="display:flex;gap:0.5rem;">
                <a href="{{ route('settings.printing') }}">Back</a>
                <button type="button" onclick="window.print()">Print</button>
            </div>
        </div>
        <div class="sheet">
            {{ $slot }}
        </div>
    </body>
</html>
