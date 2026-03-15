<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'branch_id' => Branch::factory(),
            'pin' => (string) $this->faker->unique()->numberBetween(1000, 9999),
            'job_title' => $this->faker->jobTitle(),
            'phone' => $this->faker->phoneNumber(),
            'device_uuid' => $this->faker->uuid(),
        ];
    }
}
