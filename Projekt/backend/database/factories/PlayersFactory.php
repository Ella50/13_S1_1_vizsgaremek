<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Players;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Players>
 */
class PlayersFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    protected $model = Players::class;
    public function definition(): array
    {
        return [
            //
            'name' => $this->faker->name,
            'age' => $this->faker->numberBetween(3, 40),
            'points' => $this->faker->numberBetween(0, 100),
            'address' => $this->faker->address,
        ];
    }
}
