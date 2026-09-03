<?php

namespace Tests\Feature;

use App\Mail\TwoFactorCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_without_two_factor_enabled_logs_in_directly(): void
    {
        $user = User::factory()->create(['two_factor_enabled' => false]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_two_factor_enabled_redirects_to_challenge_and_sends_a_code(): void
    {
        Mail::fake();

        $user = User::factory()->create(['two_factor_enabled' => true]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();

        Mail::assertSent(TwoFactorCodeMail::class, fn (TwoFactorCodeMail $mail) => $mail->hasTo($user->email));

        $this->assertNotNull($user->fresh()->two_factor_code_hash);
    }

    public function test_challenge_with_correct_code_logs_the_user_in(): void
    {
        Mail::fake();

        $user = User::factory()->create(['two_factor_enabled' => true]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $capturedCode = null;
        Mail::assertSent(TwoFactorCodeMail::class, function (TwoFactorCodeMail $mail) use (&$capturedCode) {
            $capturedCode = $mail->code;

            return true;
        });

        $response = $this->post('/two-factor-challenge', ['code' => $capturedCode]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_challenge_with_incorrect_code_fails_and_keeps_the_visitor_unauthenticated(): void
    {
        Mail::fake();

        $user = User::factory()->create(['two_factor_enabled' => true]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response = $this->from('/two-factor-challenge')->post('/two-factor-challenge', ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_remembering_the_device_skips_the_code_on_the_next_login(): void
    {
        Mail::fake();

        $user = User::factory()->create(['two_factor_enabled' => true]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $capturedCode = null;
        Mail::assertSent(TwoFactorCodeMail::class, function (TwoFactorCodeMail $mail) use (&$capturedCode) {
            $capturedCode = $mail->code;

            return true;
        });

        $challengeResponse = $this->post('/two-factor-challenge', [
            'code' => $capturedCode,
            'remember_device' => true,
        ]);

        $trustedCookie = $challengeResponse->headers->getCookies()[array_search(
            'modulia_trusted_device',
            array_map(fn ($cookie) => $cookie->getName(), $challengeResponse->headers->getCookies())
        )] ?? null;

        $this->assertNotNull($trustedCookie, 'Expected the trusted-device cookie to be queued on the response.');

        $this->post('/logout');
        $this->assertGuest();

        $secondLoginResponse = $this->withUnencryptedCookie($trustedCookie->getName(), $trustedCookie->getValue())
            ->post('/login', ['email' => $user->email, 'password' => 'password']);

        $secondLoginResponse->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_remembered_device_auto_logs_in_from_the_bare_login_page(): void
    {
        Mail::fake();

        $user = User::factory()->create(['two_factor_enabled' => true]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $capturedCode = null;
        Mail::assertSent(TwoFactorCodeMail::class, function (TwoFactorCodeMail $mail) use (&$capturedCode) {
            $capturedCode = $mail->code;

            return true;
        });

        $challengeResponse = $this->post('/two-factor-challenge', [
            'code' => $capturedCode,
            'remember_device' => true,
        ]);

        $cookies = $challengeResponse->headers->getCookies();
        $trustedCookie = $cookies[array_search(
            'modulia_trusted_device',
            array_map(fn ($cookie) => $cookie->getName(), $cookies)
        )];

        $this->post('/logout');
        $this->assertGuest();

        $loginPageResponse = $this->withUnencryptedCookie($trustedCookie->getName(), $trustedCookie->getValue())
            ->get('/login');

        $loginPageResponse->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_enabling_two_factor_toggles_the_flag(): void
    {
        $user = User::factory()->create(['two_factor_enabled' => false]);

        $this->actingAs($user)
            ->patch(route('two-factor.update'), ['enabled' => true])
            ->assertRedirect();

        $this->assertTrue($user->fresh()->two_factor_enabled);
    }

    public function test_disabling_two_factor_requires_the_current_password(): void
    {
        $user = User::factory()->create(['two_factor_enabled' => true]);

        $this->actingAs($user)
            ->patch(route('two-factor.update'), ['enabled' => false])
            ->assertSessionHasErrors('password');

        $this->assertTrue($user->fresh()->two_factor_enabled);

        $this->actingAs($user)
            ->patch(route('two-factor.update'), ['enabled' => false, 'password' => 'password'])
            ->assertRedirect();

        $this->assertFalse($user->fresh()->two_factor_enabled);
    }

    public function test_disabling_two_factor_deletes_trusted_devices(): void
    {
        Mail::fake();

        $user = User::factory()->create(['two_factor_enabled' => true]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $capturedCode = null;
        Mail::assertSent(TwoFactorCodeMail::class, function (TwoFactorCodeMail $mail) use (&$capturedCode) {
            $capturedCode = $mail->code;

            return true;
        });

        $this->post('/two-factor-challenge', ['code' => $capturedCode, 'remember_device' => true]);

        $this->assertSame(1, $user->trustedDevices()->count());

        $this->actingAs($user)
            ->patch(route('two-factor.update'), ['enabled' => false, 'password' => 'password']);

        $this->assertSame(0, $user->trustedDevices()->count());
    }
}
