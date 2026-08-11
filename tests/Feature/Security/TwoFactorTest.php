<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdminWithout2fa(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function makeAdminWith2fa(): array
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => ['AAAA-BBBB', 'CCCC-DDDD'],
            'two_factor_confirmed_at' => now(),
        ])->save();

        return [$user, $secret];
    }

    public function test_admin_without_2fa_is_forced_to_setup_page(): void
    {
        $admin = $this->makeAdminWithout2fa();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertRedirect(route('two-factor.setup'));
    }

    public function test_admin_without_2fa_can_still_access_setup_and_logout(): void
    {
        $admin = $this->makeAdminWithout2fa();

        // Tidak boleh infinite redirect loop ke setup page itu sendiri.
        $this->actingAs($admin)
            ->get(route('two-factor.setup'))
            ->assertOk();
    }

    public function test_non_admin_is_never_forced_to_setup_2fa(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_confirming_valid_totp_code_activates_2fa(): void
    {
        $admin = $this->makeAdminWithout2fa();
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $this->actingAs($admin)->withSession(['2fa_pending_secret' => $secret]);

        $validCode = $google2fa->getCurrentOtp($secret);

        $response = $this->actingAs($admin)
            ->withSession(['2fa_pending_secret' => $secret])
            ->post(route('two-factor.confirm'), ['code' => $validCode]);

        $response->assertRedirect(route('two-factor.recovery-codes'));
        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());
    }

    public function test_confirming_invalid_totp_code_fails(): void
    {
        $admin = $this->makeAdminWithout2fa();
        $secret = (new Google2FA)->generateSecretKey();

        $response = $this->actingAs($admin)
            ->withSession(['2fa_pending_secret' => $secret])
            ->post(route('two-factor.confirm'), ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertFalse($admin->fresh()->hasTwoFactorEnabled());
    }

    public function test_login_with_2fa_enabled_redirects_to_challenge_not_dashboard(): void
    {
        [$admin, $secret] = $this->makeAdminWith2fa();

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.challenge'));
        // Belum login penuh — auth() masih guest sampai challenge lolos.
        $this->assertGuest();
    }

    public function test_challenge_with_valid_totp_completes_login(): void
    {
        [$admin, $secret] = $this->makeAdminWith2fa();
        $google2fa = new Google2FA;

        $this->post('/login', ['email' => $admin->email, 'password' => 'password']);

        $response = $this->withSession(['2fa_awaiting_user_id' => $admin->id])
            ->post(route('two-factor.verify'), ['code' => $google2fa->getCurrentOtp($secret)]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_challenge_with_recovery_code_completes_login_and_consumes_it(): void
    {
        [$admin, $secret] = $this->makeAdminWith2fa();

        $response = $this->withSession(['2fa_awaiting_user_id' => $admin->id])
            ->post(route('two-factor.verify'), ['code' => 'AAAA-BBBB']);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);

        // Recovery code sekali pakai — tidak boleh ada lagi di list.
        $this->assertNotContains('AAAA-BBBB', $admin->fresh()->two_factor_recovery_codes);
    }

    public function test_challenge_with_wrong_code_does_not_login(): void
    {
        [$admin, $secret] = $this->makeAdminWith2fa();

        $response = $this->withSession(['2fa_awaiting_user_id' => $admin->id])
            ->post(route('two-factor.verify'), ['code' => '999999']);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_disable_2fa_requires_correct_current_password(): void
    {
        [$admin, $secret] = $this->makeAdminWith2fa();

        $this->actingAs($admin)
            ->delete(route('two-factor.disable'), ['password' => 'wrong-password'])
            ->assertSessionHasErrors('password');

        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());

        $this->actingAs($admin)
            ->delete(route('two-factor.disable'), ['password' => 'password'])
            ->assertSessionHasNoErrors();

        $this->assertFalse($admin->fresh()->hasTwoFactorEnabled());
    }
}
