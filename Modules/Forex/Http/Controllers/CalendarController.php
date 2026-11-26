<?php

namespace Modules\Forex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    /**
     * Currencies already shown on the calendar, mapped to Investing.com country ids.
     *
     * @var array<string, array{label: string, countries: string}>
     */
    private const CURRENCIES = [
        'all' => ['label' => 'All currencies', 'countries' => '5,72,4,35,25,6,12,43'],
        'USD' => ['label' => 'USD', 'countries' => '5'],
        'EUR' => ['label' => 'EUR', 'countries' => '72'],
        'GBP' => ['label' => 'GBP', 'countries' => '4'],
        'JPY' => ['label' => 'JPY', 'countries' => '35'],
        'AUD' => ['label' => 'AUD', 'countries' => '25'],
        'CAD' => ['label' => 'CAD', 'countries' => '6'],
        'CHF' => ['label' => 'CHF', 'countries' => '12'],
        'NZD' => ['label' => 'NZD', 'countries' => '43'],
    ];

    /**
     * Investing.com importance: 1 low, 2 medium, 3 high. Empty means every level.
     *
     * @var array<string, array{label: string, importance: string|null}>
     */
    private const IMPACTS = [
        'all' => ['label' => 'All impact', 'importance' => null],
        'high' => ['label' => 'High', 'importance' => '3'],
        'medium' => ['label' => 'Medium', 'importance' => '2'],
        'low' => ['label' => 'Low', 'importance' => '1'],
    ];

    /**
     * Today and this week are loaded in the frame URL.
     * The widget's own day buttons post the form without the impact checkboxes, so they show every level.
     *
     * @var array<string, array{label: string, calType: string}>
     */
    private const RANGES = [
        'today' => ['label' => 'Today', 'calType' => 'day'],
        'week' => ['label' => 'This Week', 'calType' => 'week'],
    ];

    /**
     * Standing high-impact releases, most market-moving first.
     *
     * @var list<array{name: string, moves: string}>
     */
    private const HIGH_IMPACT = [
        ['name' => 'Interest Rate Decisions (FOMC / Central Banks)', 'moves' => 'USD, Gold, Indices'],
        ['name' => 'CPI / Core CPI', 'moves' => 'USD, Gold, Indices'],
        ['name' => 'NFP / Jobs & Unemployment', 'moves' => 'USD, Gold, Indices'],
        ['name' => 'GDP (Economic Growth)', 'moves' => 'Indices, Currencies, Gold'],
        ['name' => 'PCE / Core PCE Price Index', 'moves' => 'USD, Gold, Indices'],
        ['name' => 'PPI (Producer Prices)', 'moves' => 'USD, Gold, Indices'],
        ['name' => 'Retail Sales', 'moves' => 'USD, Indices, Gold'],
        ['name' => 'ISM / PMI (Manufacturing & Services)', 'moves' => 'Indices, Currencies'],
        ['name' => 'Crude Oil Inventories (EIA) & OPEC', 'moves' => 'WTI / Brent Oil, Energy'],
        ['name' => 'Central Bank Speeches & Bond Auctions', 'moves' => 'USD, Gold, Indices, Yields'],
    ];

    public function __invoke(Request $request): View
    {
        $currency = $this->choice($request->string('currency')->toString(), self::CURRENCIES);
        $impact = $this->choice($request->string('impact')->toString(), self::IMPACTS);
        $range = $this->choice($request->string('range')->toString(), self::RANGES, 'today');

        return view('forex.calendar', [
            'user' => $request->user(),
            'todayUrl' => $this->frameUrl($currency, $impact, $range),
            'currencies' => array_map(fn (array $item): string => $item['label'], self::CURRENCIES),
            'impacts' => array_map(fn (array $item): string => $item['label'], self::IMPACTS),
            'ranges' => array_map(fn (array $item): string => $item['label'], self::RANGES),
            'currency' => $currency,
            'impact' => $impact,
            'range' => $range,
            'rangeLabel' => self::RANGES[$range]['label'],
            'highImpact' => self::HIGH_IMPACT,
        ]);
    }

    /**
     * Investing.com economic calendar. It fills the frame and loads its own events.
     * The visitor's PC clock chooses the timezone after the page loads.
     * The widget's own date, filter, and timezone controls stay off, because those posts drop importance.
     */
    private function frameUrl(string $currency, string $impact, string $range): string
    {
        $query = [
            'columns' => 'exc_flags,exc_currency,exc_importance,exc_actual,exc_forecast,exc_previous',
            'countries' => self::CURRENCIES[$currency]['countries'],
            'calType' => self::RANGES[$range]['calType'],
            'lang' => '1',
        ];

        $importance = self::IMPACTS[$impact]['importance'];

        if (is_string($importance)) {
            $query['importance'] = $importance;
        }

        return 'https://sslecal2.investing.com?'.http_build_query($query);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function choice(string $value, array $options, string $fallback = 'all'): string
    {
        return array_key_exists($value, $options) ? $value : $fallback;
    }
}
