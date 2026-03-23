<?php

namespace Database\Factories;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdminFactory extends Factory
{
    protected $model = Admin::class;

    public function definition(): array
    {
        return [
            'username'      => $this->faker->unique()->userName(),
            'password_hash' => bcrypt('password'),
            'full_name'     => $this->faker->name(),
            'email'         => $this->faker->unique()->safeEmail(),
            'is_super_admin'=> false,
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(['is_super_admin' => true]);
    }

    public function withPassword(string $password): static
    {
        return $this->state(['password_hash' => bcrypt($password)]);
    }
}
