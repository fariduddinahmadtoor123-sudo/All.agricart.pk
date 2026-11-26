<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForexCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login_from_the_calendar(): void
    {
        $this->get('/forex/calendar')->assertRedirect('/login');
    }

    public function test_calendar_page_opens_on_today_beside_a_short_legend(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('forex.calendar'))
            ->assertOk()
            ->assertSeeInOrder([
                'href="'.route('forex.watch').'"',
                'Market Watch',
                'href="'.route('forex.market-watch').'"',
                'Trade Terminal',
                'href="'.route('forex.calendar').'"',
                'Calendar',
            ], false)
            ->assertSee('Today')
            ->assertSee('This Week')
            ->assertDontSee('This week')
            ->assertSeeInOrder([
                'Legend',
                'Speech',
                'Preliminary Release',
                'Revised Release',
                'Retrieving Data',
                'Low Volatility Expected',
                'Moderate Volatility Expected',
                'High Volatility Expected',
            ])
            ->assertSee('https://sslecal2.investing.com?', false)
            ->assertSee('calType=day', false)
            ->assertDontSee('features=', false)
            ->assertDontSee('calType=week', false)
            ->assertDontSee('timeselector', false)
            ->assertDontSee('timeZone=24', false)
            ->assertSee("'300': 77", false)
            ->assertDontSee('importance=', false)
            ->assertSee('https://www.investing.com/', false)
            ->assertSeeInOrder(['Forex Trading', 'Calendar'], false)
            ->assertDontSee('tracking-wide">Calendar', false)
            ->assertSeeInOrder([
                'Currency',
                'All currencies',
                'USD',
                'EUR',
                'GBP',
                'JPY',
                'AUD',
                'CAD',
                'CHF',
                'NZD',
                'Impact',
                'All impact',
                'High',
                'Medium',
                'Low',
                'Day',
                'Today',
                'This Week',
            ])
            ->assertSeeInOrder([
                'High impact',
                'Interest Rate Decisions (FOMC / Central Banks)',
                'USD, Gold, Indices',
                'CPI / Core CPI',
                'NFP / Jobs & Unemployment',
                'GDP (Economic Growth)',
                'Indices, Currencies, Gold',
                'PCE / Core PCE Price Index',
                'PPI (Producer Prices)',
                'Retail Sales',
                'USD, Indices, Gold',
                'ISM / PMI (Manufacturing & Services)',
                'Indices, Currencies',
                'Crude Oil Inventories (EIA) & OPEC',
                'WTI / Brent Oil, Energy',
                'Central Bank Speeches & Bond Auctions',
                'USD, Gold, Indices, Yields',
            ])
            ->assertSee('text-red-700', false);
    }

    public function test_calendar_filters_narrow_the_widget_to_one_currency_and_impact(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('forex.calendar', ['currency' => 'USD', 'impact' => 'high']))
            ->assertOk()
            ->assertSeeInOrder([
                'name="currency"',
                'value="USD" selected',
                'name="impact"',
                'value="high" selected',
            ], false)
            ->assertSee('countries=5&amp;', false)
            ->assertSee('importance=3', false)
            ->assertSee('calType=day', false)
            ->assertDontSee('countries=5%2C72', false);
    }

    public function test_unknown_calendar_filters_fall_back_to_every_currency_and_impact(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('forex.calendar', ['currency' => 'NOPE', 'impact' => 'urgent', 'range' => 'nope']))
            ->assertOk()
            ->assertSeeInOrder([
                'name="currency"',
                'value="all" selected',
                'name="impact"',
                'value="all" selected',
            ], false)
            ->assertSee('countries=5%2C72%2C4%2C35%2C25%2C6%2C12%2C43', false)
            ->assertSee('value="today" selected', false)
            ->assertDontSee('importance=', false);
    }

    public function test_this_week_keeps_the_chosen_impact(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('forex.calendar', ['impact' => 'medium', 'range' => 'week']))
            ->assertOk()
            ->assertSee('value="medium" selected', false)
            ->assertSee('value="week" selected', false)
            ->assertSee('calType=week', false)
            ->assertSee('importance=2', false)
            ->assertDontSee('timeselector', false);
    }
}
