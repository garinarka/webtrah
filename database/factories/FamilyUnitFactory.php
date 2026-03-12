<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FamilyUnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'     => 'Keluarga ' . fake()->lastName(),
            'settings' => [],
        ];
    }
}
