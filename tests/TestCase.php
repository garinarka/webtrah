<?php

namespace Tests;

use App\Models\FamilyUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    // helper: buat user dengan role
    protected function makeAdmin(array $attrs = []): User
    {
        $admin = User::factory()->create($attrs)->assignRole('admin');

        // Default: admin di test dianggap "siap pakai" (2FA sudah aktif),
        // supaya EnsureAdminHasTwoFactorEnabled tidak paksa redirect ke
        // /two-factor/setup di semua test lama yang expect response normal.
        // Test yang KHUSUS menguji alur 2FA (belum aktif -> dipaksa setup)
        // punya helper sendiri, lihat TwoFactorTest::makeAdminWithout2fa().
        $admin->forceFill([
            'two_factor_secret' => 'test-secret-not-real',
            'two_factor_recovery_codes' => ['TEST-0000'],
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $admin;
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
