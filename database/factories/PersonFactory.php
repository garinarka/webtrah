<?php

namespace Database\Factories;

use App\Models\FamilyUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonFactory extends Factory
{
    public function definition(): array
    {
        $birthYear = fake()->numberBetween(1930, 2010);

        return [
            'family_unit_id' => FamilyUnit::factory(),
            'created_by'     => User::factory(),
            'status'         => 'active',
            'gender'         => fake()->randomElement(['male', 'female']),
            'birth_date'     => fake()->dateTimeBetween("{$birthYear}-01-01", "{$birthYear}-12-31")->format('Y-m-d'),
            'birth_accuracy' => 'exact',
            'death_date'     => null,
            'death_accuracy' => 'unknown',
            'display_name'   => fake()->name(),
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    public function male(): static
    {
        return $this->state(['gender' => 'male']);
    }

    public function female(): static
    {
        return $this->state(['gender' => 'female']);
    }

    public function deceased(): static
    {
        return $this->state([
            'death_date'     => fake()->dateTimeBetween('-10 years', 'now')->format('Y-m-d'),
            'death_accuracy' => 'exact',
        ]);
    }
}
