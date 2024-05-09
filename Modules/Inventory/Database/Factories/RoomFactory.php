<?php

namespace Modules\Inventory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Inventory\App\Models\Room::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}

