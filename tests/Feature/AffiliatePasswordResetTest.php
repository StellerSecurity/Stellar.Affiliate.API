<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AffiliatePasswordResetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        config()->set('stellar-notifications.base_url', 'https://notifications.example');
        config()->set('stellar-notifications.basic_username', 'test-service');
        config()->set('stellar-notifications.basic_password', 'test-password');
        Http::preventStrayRequests();
        Http::fake(['notifications.example/*' => Http::response(['data' => ['deliveries_created' => 1]])]);

        // Auth tests need only the auth schema; financial repair migrations use MySQL.
        $this->artisan('migrate', ['--path' => [
            'database/migrations/2014_10_12_000000_create_users_table.php',
            'database/migrations/2014_10_12_100000_create_password_reset_tokens_table.php',
            'database/migrations/2025_12_08_202257_add_api_token_to_users_table.php',
            'database/migrations/2026_08_10_220001_add_affiliate_admin_role_to_users_table.php',
            'database/migrations/2025_12_08_200001_create_affiliates_table.php',
            'database/migrations/2025_12_08_200002_create_affiliate_campaigns_table.php',
        ], '--force' => true])->assertExitCode(0);
    }

    private function user(string $email = 'affiliate@example.com'): User
    {
        $user = User::create(['name' => 'Test Affiliate', 'email' => $email, 'password' => 'old-password-123']);
        $user->forceFill(['remember_token' => 'old-remember-token', 'api_token' => hash('sha256', 'old-api-token-'.$email)])->save();

        return $user;
    }

    private function resetData(User $user, string $token): array
    {
        return ['email' => $user->email, 'token' => $token, 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'];
    }

    public function test_login_links_to_accessible_password_recovery_forms(): void
    {
        $this->get(route('affiliate.login'))->assertOk()->assertSee(route('affiliate.password.request'));
        $this->get(route('affiliate.password.request'))->assertOk()->assertSee('Send reset link');
        $this->get(route('affiliate.password.reset', ['token' => str_repeat('a', 64), 'email' => 'affiliate@example.com']))
            ->assertOk()->assertSee('affiliate@example.com')->assertSee('Confirm new password')
            ->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_request_sends_a_trusted_link_with_a_hashed_expiring_token(): void
    {
        $user = $this->user();
        $this->withServerVariables(['HTTP_HOST' => 'untrusted.example'])
            ->post('/affiliate/forgot-password', ['email' => '  AFFILIATE@EXAMPLE.COM  '])
            ->assertRedirect()->assertSessionHas('status');

        Http::assertSent(function ($request) use ($user): bool {
            $url = $request['payload']['reset_url'];
            $token = basename(parse_url($url, PHP_URL_PATH));
            $stored = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');
            $this->assertNotSame($token, $stored);
            $this->assertTrue(Hash::check($token, $stored));
            $this->assertTrue(Password::broker('users')->tokenExists($user, $token));

            return str_starts_with($url, 'https://stellarafi.com/affiliate/reset-password/')
                && str_contains($url, 'email=affiliate%40example.com')
                && $request['event_name'] === 'affiliate_password_reset'
                && $request['payload']['expires_in_minutes'] === 60
                && $request->hasHeader('Authorization');
        });
    }

    public function test_existing_unknown_and_recently_requested_accounts_get_identical_responses(): void
    {
        $user = $this->user();
        $this->post(route('affiliate.password.email'), ['email' => $user->email]);
        $message = session('status');
        $this->post(route('affiliate.password.email'), ['email' => 'unknown@example.com'])
            ->assertRedirect(route('affiliate.password.request'))->assertSessionHas('status', $message);
        $this->post(route('affiliate.password.email'), ['email' => $user->email])
            ->assertRedirect(route('affiliate.password.request'))->assertSessionHas('status', $message);
        Http::assertSentCount(1);
        $this->assertDatabaseCount('password_reset_tokens', 1);
    }

    public function test_password_reset_invalidates_token_remember_cookie_and_api_access_and_allows_login(): void
    {
        Event::fake([PasswordReset::class]);
        $user = $this->user();
        $this->post(route('affiliate.password.email'), ['email' => $user->email])->assertRedirect();
        $url = Http::recorded()[0][0]['payload']['reset_url'];
        $token = basename(parse_url($url, PHP_URL_PATH));
        $this->get($url)->assertOk()->assertSee('Confirm new password');
        $this->post(route('affiliate.password.update'), $this->resetData($user, $token))
            ->assertRedirect(route('affiliate.login'))->assertSessionHas('status');
        $user->refresh();
        $this->assertTrue(Hash::check('new-password-123', $user->password));
        $this->assertFalse(Hash::check('old-password-123', $user->password));
        $this->assertNotSame('old-remember-token', $user->remember_token);
        $this->assertNull($user->api_token);
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->assertGuest('web');
        Event::assertDispatched(PasswordReset::class);

        $this->post(route('affiliate.login.post'), ['email' => $user->email, 'password' => 'old-password-123'])
            ->assertSessionHasErrors('email');
        $this->post(route('affiliate.login.post'), ['email' => $user->email, 'password' => 'new-password-123'])
            ->assertRedirect(route('affiliate.onboarding'));
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_used_invalid_expired_and_other_account_tokens_are_rejected(): void
    {
        $user = $this->user();
        $other = $this->user('other@example.com');
        $token = Password::broker('users')->createToken($user);
        $this->post(route('affiliate.password.update'), $this->resetData($other, $token))->assertSessionHasErrors('email');
        $this->post(route('affiliate.password.update'), $this->resetData($user, str_repeat('f', 64)))->assertSessionHasErrors('email');
        $this->travel(61)->minutes();
        $this->post(route('affiliate.password.update'), $this->resetData($user, $token))->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('old-password-123', $user->fresh()->password));
        $this->travelBack();
        $token = Password::broker('users')->createToken($user);
        $this->post(route('affiliate.password.update'), $this->resetData($user, $token))->assertRedirect(route('affiliate.login'));
        $this->post(route('affiliate.password.update'), $this->resetData($user, $token))->assertSessionHasErrors('email');
    }

    #[DataProvider('invalidPasswords')]
    public function test_password_validation_does_not_flash_secrets(string $password, string $confirmation): void
    {
        $user = $this->user();
        $token = Password::broker('users')->createToken($user);
        $data = array_merge($this->resetData($user, $token), ['password' => $password, 'password_confirmation' => $confirmation]);
        $this->from(route('affiliate.password.reset', ['token' => $token]))
            ->post(route('affiliate.password.update'), $data)->assertSessionHasErrors('password')
            ->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation')
            ->assertSessionMissing('_old_input.token');
        $this->assertTrue(Hash::check('old-password-123', $user->fresh()->password));
        $this->assertTrue(Password::broker('users')->tokenExists($user, $token));
    }

    public static function invalidPasswords(): array
    {
        return [['short', 'short'], ['long-password', 'mismatch'], [str_repeat('a', 256), str_repeat('a', 256)]];
    }

    public function test_delivery_failure_keeps_response_private_and_allows_retry(): void
    {
        $user = $this->user();
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['notifications.example/*' => Http::response(['message' => 'Unavailable'], 503)]);
        $this->post(route('affiliate.password.email'), ['email' => $user->email])
            ->assertRedirect(route('affiliate.password.request'))->assertSessionHas('status');
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->assertTrue(Hash::check('old-password-123', $user->fresh()->password));
    }

    public function test_requesting_a_new_link_invalidates_the_previous_link(): void
    {
        $user = $this->user();
        $this->post(route('affiliate.password.email'), ['email' => $user->email])->assertRedirect();
        $oldToken = basename(parse_url(Http::recorded()[0][0]['payload']['reset_url'], PHP_URL_PATH));
        $this->travel(61)->seconds();
        $this->post(route('affiliate.password.email'), ['email' => $user->email])->assertRedirect();
        Http::assertSentCount(2);
        $this->assertFalse(Password::broker('users')->tokenExists($user, $oldToken));
        $this->travelBack();
    }

    public function test_invalid_email_does_not_send_a_reset_request(): void
    {
        $this->post(route('affiliate.password.email'), ['email' => 'not-an-email'])->assertSessionHasErrors('email');
        $this->post(route('affiliate.password.email'), ['email' => ['affiliate@example.com']])->assertSessionHasErrors('email');
        Http::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_reset_requests_and_password_attempts_are_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('affiliate.password.email'), ['email' => 'unknown@example.com'])->assertRedirect();
        }
        $this->post(route('affiliate.password.email'), ['email' => 'unknown@example.com'])->assertStatus(429);
        $data = ['email' => 'unknown@example.com', 'token' => str_repeat('a', 64), 'password' => 'new-password', 'password_confirmation' => 'new-password'];
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('affiliate.password.update'), $data)->assertRedirect();
        }
        $this->post(route('affiliate.password.update'), $data)->assertStatus(429);
        Http::assertNothingSent();
    }

    public function test_previous_authenticated_session_cannot_survive_a_password_reset(): void
    {
        $user = $this->user();
        $oldHash = $user->password;
        $token = Password::broker('users')->createToken($user);
        $this->post(route('affiliate.password.update'), $this->resetData($user, $token))->assertRedirect(route('affiliate.login'));

        // Simulate another browser still presenting its pre-reset session.
        Auth::forgetGuards();
        $this->actingAs($user->fresh(), 'web')->withSession(['password_hash_web' => $oldHash])
            ->get(route('affiliate.login'))->assertRedirect(route('login'));
        $this->assertGuest('web');
    }
}
