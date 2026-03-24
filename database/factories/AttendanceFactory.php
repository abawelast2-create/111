<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'employee_id'     => Employee::factory(),
            'type'            => $this->faker->randomElement(['in', 'out']),
            'timestamp'       => now(),
            'attendance_date' => today()->toDateString(),
            'late_minutes'    => 0,
            'latitude'        => 24.7136,
            'longitude'       => 46.6753,
        ];
    }

    public function checkIn(): static
    {
        return $this->state(fn () => ['type' => 'in']);
    }

    public function checkOut(): static
    {
        return $this->state(fn () => ['type' => 'out']);
    }

    public function late(int $minutes = 15): static
    {
        return $this->state(fn () => ['late_minutes' => $minutes]);
    }
}
