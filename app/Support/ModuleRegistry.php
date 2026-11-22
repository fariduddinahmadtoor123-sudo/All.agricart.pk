<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

class ModuleRegistry
{
    /**
     * @var list<array{key: string, label: string, route: string, parameters: array<string, string>, patterns: list<string>, soon: bool}>
     */
    private static array $menu = [];

    public static function boot(): void
    {
        if (app()->bound('modules.booted')) {
            return;
        }

        app()->instance('modules.booted', true);
        self::$menu = [];

        foreach (self::names() as $name) {
            try {
                /** @var array{key: string, label: string, route: string, patterns: list<string>, soon?: bool, provider: class-string} $meta */
                $meta = require base_path("Modules/{$name}/module.php");

                if (! self::registerProvider($meta['provider'])) {
                    continue;
                }

                self::$menu[] = [
                    'key' => $meta['key'],
                    'label' => $meta['label'],
                    'route' => $meta['route'],
                    'parameters' => [],
                    'patterns' => $meta['patterns'],
                    'soon' => (bool) ($meta['soon'] ?? false),
                ];
            } catch (Throwable $exception) {
                Log::error($exception->getMessage(), ['exception' => $exception]);
            }
        }
    }

    /**
     * @param  class-string  $provider
     */
    public static function registerProvider(string $provider): bool
    {
        try {
            app()->register($provider);

            return true;
        } catch (Throwable $exception) {
            Log::error($exception->getMessage(), ['exception' => $exception]);

            return false;
        }
    }

    /**
     * @return list<array{key: string, label: string, route: string, parameters: array<string, string>, patterns: list<string>, soon: bool}>
     */
    public static function menu(): array
    {
        self::boot();

        return self::$menu;
    }

    /**
     * @return list<string>
     */
    private static function names(): array
    {
        return [
            'Dashboard',
            'Approvals',
            'Contacts',
            'Catalog',
            'Purchasing',
            'Sales',
            'Store',
            'Logistics',
            'Service',
            'Reports',
            'Staff',
            'Finance',
            'Marketplace',
            'Docs',
            'PersonalVault',
            'Forex',
            'ExternalApps',
            'Settings',
        ];
    }
}
