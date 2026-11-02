<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $attrs = []): User
    {
        return User::factory()->create($attrs + ['username' => 'boss', 'email' => 'boss@example.com', 'full_name' => 'The Boss']);
    }

    public function test_password_reset_by_email(): void
    {
        Notification::fake();
        $user = $this->admin();

        $this->post('/admin/forgot-password', ['email' => 'boss@example.com'])->assertSessionHas('success');
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) use (&$token) {
            $token = $n->token;

            return true;
        });

        // The e-mailed link points at the admin panel.
        $this->get("/admin/reset-password/{$token}?email=boss@example.com")->assertOk()->assertSee('Choose a new password');

        $this->post('/admin/reset-password', [
            'token' => $token, 'email' => 'boss@example.com', 'password' => 'NewSecret99', 'password_confirmation' => 'NewSecret99',
        ])->assertRedirect(route('admin.login'));

        $this->assertSame(sha1('NewSecret99'), $user->fresh()->password, 'stays SHA1 until LEGACY_PASSWORD_UPGRADE is on');
        $this->post('/admin/login', ['username' => 'boss', 'password' => 'NewSecret99'])->assertRedirect('/admin');
    }

    public function test_reset_does_not_reveal_whether_an_address_exists_and_rejects_bad_tokens(): void
    {
        Notification::fake();
        $this->admin();

        $this->post('/admin/forgot-password', ['email' => 'nobody@example.com'])
            ->assertSessionHas('success', 'If that address belongs to an account, a password reset link has been sent to it.');
        Notification::assertNothingSent();

        $this->post('/admin/reset-password', [
            'token' => 'bogus', 'email' => 'boss@example.com', 'password' => 'NewSecret99', 'password_confirmation' => 'NewSecret99',
        ])->assertSessionHasErrors('email');
    }

    public function test_change_password_needs_the_current_password_and_a_strong_new_one(): void
    {
        $user = $this->admin(['password' => sha1('OldSecret1')]);
        $this->actingAs($user);

        $this->put('/admin/account/password', ['current_password' => 'wrong', 'password' => 'NewSecret99', 'password_confirmation' => 'NewSecret99'])
            ->assertSessionHasErrors('current_password');
        $this->put('/admin/account/password', ['current_password' => 'OldSecret1', 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors('password');
        $this->put('/admin/account/password', ['current_password' => 'OldSecret1', 'password' => 'OldSecret1', 'password_confirmation' => 'OldSecret1'])
            ->assertSessionHasErrors('password');

        $this->put('/admin/account/password', ['current_password' => 'OldSecret1', 'password' => 'NewSecret99', 'password_confirmation' => 'NewSecret99'])
            ->assertSessionHas('success');
        $this->assertSame(sha1('NewSecret99'), $user->fresh()->password);
    }

    public function test_profile_email_must_stay_unique(): void
    {
        User::factory()->create(['email' => 'taken@example.com', 'username' => 'other']);
        $user = $this->admin();

        $this->actingAs($user)->put('/admin/account/profile', ['full_name' => 'X', 'email' => 'taken@example.com'])->assertSessionHasErrors('email');
        $this->put('/admin/account/profile', ['full_name' => 'New Name', 'email' => 'boss@example.com'])->assertSessionHas('success');
        $this->assertSame('New Name', $user->fresh()->full_name);
    }

    public function test_two_factor_setup_login_challenge_and_recovery_codes(): void
    {
        $user = $this->admin(['password' => sha1('Secret123')]);
        $google = new Google2FA;

        // --- set up
        $this->actingAs($user)->post('/admin/account/two-factor')->assertRedirect();
        $secret = $user->fresh()->two_factor_secret;
        $this->assertNotEmpty($secret);
        $this->assertNotSame($secret, DB::table('users')->where('id', $user->id)->value('two_factor_secret'), 'secret is encrypted at rest');
        $this->get('/admin/account')->assertOk()->assertSee('<svg', false);

        $this->post('/admin/account/two-factor/confirm', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post('/admin/account/two-factor/confirm', ['code' => $google->getCurrentOtp($secret)])->assertSessionHas('recovery_codes');
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
        $recovery = $user->fresh()->two_factor_recovery_codes;
        $this->assertCount(8, $recovery);

        // --- sign in again: password alone is not enough
        auth()->logout();
        $this->post('/admin/login', ['username' => 'boss', 'password' => 'Secret123'])->assertRedirect(route('admin.two-factor'));
        $this->assertGuest();
        $this->get('/admin')->assertRedirect(route('admin.login'));

        $this->post('/admin/two-factor', ['code' => '123456'])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post('/admin/two-factor', ['code' => $google->getCurrentOtp($secret)])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);

        // --- a recovery code signs in once, then is used up
        auth()->logout();
        $this->post('/admin/login', ['username' => 'boss', 'password' => 'Secret123']);
        $this->post('/admin/two-factor', ['recovery_code' => $recovery[0]])->assertRedirect('/admin');
        $this->assertCount(7, $user->fresh()->two_factor_recovery_codes);

        auth()->logout();
        $this->post('/admin/login', ['username' => 'boss', 'password' => 'Secret123']);
        $this->post('/admin/two-factor', ['recovery_code' => $recovery[0]])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_two_factor_challenge_is_rate_limited_and_needs_the_password_step_first(): void
    {
        $this->get('/admin/two-factor')->assertRedirect(route('admin.login'));
        $this->post('/admin/two-factor', ['code' => '123456'])->assertRedirect(route('admin.login'));

        $user = $this->admin(['password' => sha1('Secret123')]);
        $user->forceFill(['two_factor_secret' => (new Google2FA)->generateSecretKey(), 'two_factor_confirmed_at' => now()])->save();
        $this->post('/admin/login', ['username' => 'boss', 'password' => 'Secret123']);

        foreach (range(1, 5) as $i) {
            $this->post('/admin/two-factor', ['code' => '111111'])->assertSessionHasErrors('code');
        }
        $this->post('/admin/two-factor', ['code' => (new Google2FA)->getCurrentOtp($user->fresh()->two_factor_secret)])
            ->assertSessionHasErrors('code');   // locked out even with the right code
        $this->assertGuest();
    }

    public function test_turning_two_factor_off_needs_the_password(): void
    {
        $user = $this->admin(['password' => sha1('Secret123')]);
        $user->forceFill(['two_factor_secret' => (new Google2FA)->generateSecretKey(), 'two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => ['a']])->save();
        $this->actingAs($user);

        $this->delete('/admin/account/two-factor', ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());

        $this->delete('/admin/account/two-factor', ['password' => 'Secret123'])->assertSessionHas('success');
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
        $this->assertNull($user->fresh()->two_factor_secret);
    }
}
