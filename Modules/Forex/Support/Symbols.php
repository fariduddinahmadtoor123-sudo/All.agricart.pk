<?php

namespace Modules\Forex\Support;

class Symbols
{
    public static function max(): int
    {
        return max(1, min(15, (int) config('forex.max_symbols', 15)));
    }

    /**
     * @return array<string, array{key: string, label: string, symbols: list<string>}>
     */
    public static function categories(): array
    {
        $out = [];

        foreach (config('forex.categories', []) as $key => $category) {
            if (! is_array($category)) {
                continue;
            }

            $symbols = collect($category['symbols'] ?? [])
                ->map(fn (mixed $symbol): string => strtoupper(trim((string) $symbol)))
                ->filter(fn (string $symbol): bool => $symbol !== '' && strlen($symbol) <= 32)
                ->unique()
                ->values()
                ->all();

            if ($symbols === []) {
                continue;
            }

            $out[(string) $key] = [
                'key' => (string) $key,
                'label' => (string) ($category['label'] ?? ucfirst((string) $key)),
                'symbols' => $symbols,
            ];
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    public static function allowed(): array
    {
        $symbols = collect(self::categories())
            ->flatMap(fn (array $category): array => $category['symbols'])
            ->unique()
            ->take(self::max())
            ->values()
            ->all();

        return $symbols !== [] ? $symbols : ['EURUSD'];
    }

    public static function allows(string $symbol): bool
    {
        return in_array(strtoupper($symbol), self::allowed(), true);
    }

    public static function categoryFor(string $symbol): ?string
    {
        $symbol = strtoupper($symbol);

        foreach (self::categories() as $key => $category) {
            if (in_array($symbol, $category['symbols'], true)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $symbols
     * @return list<string>
     */
    public static function onlyAllowed(array $symbols): array
    {
        $allowed = array_flip(self::allowed());

        return collect($symbols)
            ->map(fn (mixed $symbol): string => strtoupper(trim((string) $symbol)))
            ->filter(fn (string $symbol): bool => isset($allowed[$symbol]))
            ->unique()
            ->take(self::max())
            ->values()
            ->all();
    }

    /**
     * @return array<string, list<string>>
     */
    public static function groupedAllowed(): array
    {
        $allowed = array_flip(self::allowed());
        $grouped = [];

        foreach (self::categories() as $key => $category) {
            $grouped[$key] = array_values(array_filter(
                $category['symbols'],
                fn (string $symbol): bool => isset($allowed[$symbol]),
            ));
        }

        return $grouped;
    }
}
