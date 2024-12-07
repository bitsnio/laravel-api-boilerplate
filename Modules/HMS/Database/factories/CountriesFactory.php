<?php

namespace Modules\HMS\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CountriesFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\HMS\App\Models\Countries::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->country,
            'code' => strtoupper(fake()->countryCode), // Optional: Generate uppercase country codes
        ];
    }
}

