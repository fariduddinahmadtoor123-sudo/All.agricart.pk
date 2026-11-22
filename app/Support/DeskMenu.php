<?php

namespace App\Support;

class DeskMenu
{
    /**
     * @return list<array{key: string, label: string, route: string, parameters: array<string, string>, patterns: list<string>, soon: bool}>
     */
    public static function items(): array
    {
        return ModuleRegistry::menu();
    }

    /**
     * @param  array{patterns: list<string>}  $item
     */
    public static function isCurrent(array $item): bool
    {
        return request()->routeIs(...$item['patterns']);
    }

    public static function labelForRoute(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        foreach (self::items() as $item) {
            if ($item['route'] !== $name || in_array($item['key'], ['dashboard', 'forex', 'settings', 'docs'], true)) {
                continue;
            }

            return $item['label'];
        }

        return null;
    }
}
