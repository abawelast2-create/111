<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'latitude' => $this->faker->latitude(24.5, 25.0),
            'longitude' => $this->faker->longitude(46.5, 47.0),
            'geofence_radius' => 200,
        ];
    }
}
