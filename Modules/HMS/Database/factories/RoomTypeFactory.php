<?php

namespace Modules\HMS\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RoomTypeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\HMS\Models\RoomType::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}

