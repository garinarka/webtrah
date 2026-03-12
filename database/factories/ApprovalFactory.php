<?php

namespace Database\Factories;

use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApprovalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'approvable_type' => Person::class,
            'approvable_id'   => Person::factory(),
            'action'          => fake()->randomElement(['create', 'update', 'delete']),
            'changes'         => [
                'display_name' => ['old' => null, 'new' => fake()->name()],
            ],
            'status'       => 'pending',
            'requested_by' => User::factory(),
            'approved_by'  => null,
            'approved_at'  => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending', 'approved_by' => null, 'approved_at' => null]);
    }

    public function approved(): static
    {
        return $this->state([
            'status'      => 'approved',
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status'           => 'rejected',
            'approved_by'      => User::factory(),
            'approved_at'      => now(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    public function forCreate(): static
    {
        return $this->state(['action' => 'create']);
    }

    public function forUpdate(): static
    {
        return $this->state(['action' => 'update']);
    }

    public function forDelete(): static
    {
        return $this->state(['action' => 'delete']);
    }
}
