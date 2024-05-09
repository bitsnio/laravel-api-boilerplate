<?php

namespace Modules\HMS\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\HMS\App\Models\Property::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}

