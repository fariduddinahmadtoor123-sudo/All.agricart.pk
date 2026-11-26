<x-print.layout :profile="$profile" :title="$sampleTitle">
    <h1 style="margin:0 0 0.75rem;font-size:1.25rem;">{{ $sampleTitle }}</h1>
    <p style="margin:0 0 1rem;line-height:1.5;">{{ $sampleBody }}</p>
    <table style="width:100%;border-collapse:collapse;font-size:0.9rem;">
        <thead>
            <tr>
                <th style="text-align:left;border-bottom:1px solid #ccc;padding:0.4rem 0;">Field</th>
                <th style="text-align:left;border-bottom:1px solid #ccc;padding:0.4rem 0;">Value</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding:0.4rem 0;">Profile</td>
                <td style="padding:0.4rem 0;">{{ $profile->localizedName() }}</td>
            </tr>
            <tr>
                <td style="padding:0.4rem 0;">Size</td>
                <td style="padding:0.4rem 0;">{{ $profile->dimensionsLabel() }}</td>
            </tr>
            <tr>
                <td style="padding:0.4rem 0;">Category</td>
                <td style="padding:0.4rem 0;">{{ $profile->categoryLabel() }}</td>
            </tr>
            <tr>
                <td style="padding:0.4rem 0;">Printer hint</td>
                <td style="padding:0.4rem 0;">{{ $profile->printer_hint ?: '—' }}</td>
            </tr>
        </tbody>
    </table>
</x-print.layout>
