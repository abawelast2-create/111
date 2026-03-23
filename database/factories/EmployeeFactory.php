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
            'name'          => $this->faker->name(),
            'branch_id'     => Branch::factory(),
            'pin'           => str_pad((string) $this->faker->unique()->numberBetween(1000, 9999), 4, '0', STR_PAD_LEFT),
            'job_title'     => $this->faker->jobTitle(),
            'phone'         => '05' . $this->faker->numerify('########'),
            'unique_token'  => bin2hex(random_bytes(32)),
            'is_active'     => true,
            'device_bind_mode' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function deleted(): static
    {
        return $this->state(['deleted_at' => now()]);
    }
}
