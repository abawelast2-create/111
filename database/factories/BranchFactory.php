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
            'name'                 => $this->faker->unique()->company(),
            'latitude'             => 24.7136,
            'longitude'            => 46.6753,
            'geofence_radius'      => 500,
            'is_active'            => true,
            'work_start_time'      => '08:00:00',
            'work_end_time'        => '16:00:00',
            'check_in_start_time'  => '00:00:00',
            'check_in_end_time'    => '23:59:00',
            'check_out_start_time' => '00:00:00',
            'check_out_end_time'   => '23:59:00',
            'allow_overtime'       => false,
        ];
    }

    public function withOvertime(): static
    {
        return $this->state([
            'allow_overtime'       => true,
            'overtime_start_after' => 30,
            'overtime_min_duration'=> 30,
        ]);
    }
}
