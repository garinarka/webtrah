<?php

namespace Tests;

use App\Models\FamilyUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    // helper: buat user dengan role
    protected function makeAdmin(array $attrs = []): User
    {
        return User::factory()->create($attrs)->assignRole('admin');
    }

    protected function makeModerator(array $attrs = []): User
    {
        return User::factory()->create($attrs)->assignRole('moderator');
    }

    protected function makeUser(array $attrs = []): User
    {
        return User::factory()->create($attrs)->assignRole('user');
    }

    // helper: keluarga unit default
    protected function makeFamily(array $attrs = []): FamilyUnit
    {
        return FamilyUnit::factory()->create($attrs);
    }

    // helper: setup roles & permissions (dipanggil sekali per test)
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }
}
