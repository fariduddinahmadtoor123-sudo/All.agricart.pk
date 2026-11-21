<?php

namespace Database\Factories;

use App\Models\ColorScheme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ColorScheme>
 */
class ColorSchemeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'page' => '#f5ebe0',
            'card' => '#edede9',
            'soft' => '#e3d5ca',
            'line' => '#d6ccc2',
            'primary' => '#d5bdaf',
        ];
    }
}
