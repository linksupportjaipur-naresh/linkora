<?php

namespace Tests\Feature;

use App\Mail\LoginCodeMail;
use App\Models\LoginCode;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailCodeLoginTest extends TestCase
{
    use DatabaseTransactions;

    /** Submit the email step and return the code that was "emailed". */
    private function requestCode(string $email, string $intent = 'register', array $extra = []): string
    {
        Mail::fake();

        $this->post('/auth/email', ['email' => $email, 'intent' => $intent] + $extra)
            ->assertRedirect(route('otp.show'));

        $code = null;
        Mail::assertSent(LoginCodeMail::class, function (LoginCodeMail $mail) use ($email, &$code) {
            $code = $mail->code;

            return $mail->hasTo(strtolower($email));
        });

        return $code;
    }

    public function test_sign_up_with_email_code_creates_account_and_logs_in(): void
    {
        $code = $this->requestCode('Otp.NewUser@Example.com');

        $this->get('/verify')->assertOk()->assertSee('otp.newuser@example.com');

        $this->post('/verify', ['code' => $code])->assertRedirect(route('dashboard'));

        $user = User::where('email', 'otp.newuser@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('otp.newuser', $user->username);
        $this->assertSame('Otp Newuser', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->isActive());
    }

    public function test_claimed_username_from_home_page_is_used(): void
    {
        $code = $this->requestCode('claimer@example.com', 'register', ['username' => 'my.brand']);
        $this->post('/verify', ['code' => $code]);

        $this->assertSame('my.brand', User::where('email', 'claimer@example.com')->value('username'));
    }

    public function test_existing_user_logs_in_with_code(): void
    {
        $user = User::create(['name' => 'Old', 'username' => 'old.user', 'email' => 'old.user@example.com', 'password' => Hash::make('x')]);

        $code = $this->requestCode('old.user@example.com', 'login');
        $this->post('/verify', ['code' => $code])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::where('email', 'old.user@example.com')->count());
    }

    public function test_login_with_unknown_email_shows_error_and_sends_nothing(): void
    {
        Mail::fake();

        $this->from('/login')->post('/auth/email', ['email' => 'nobody@example.com', 'intent' => 'login'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        Mail::assertNothingSent();
    }

    public function test_wrong_code_is_rejected_and_locks_after_five_tries(): void
    {
        $code = $this->requestCode('wrong.code@example.com');
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($i = 0; $i < LoginCode::MAX_ATTEMPTS; $i++) {
            $this->from('/verify')->post('/verify', ['code' => $wrong])->assertSessionHasErrors('code');
        }

        // Even the right code no longer works once the attempts are used up.
        $this->from('/verify')->post('/verify', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_expired_code_is_rejected(): void
    {
        $code = $this->requestCode('expired@example.com');
        LoginCode::where('email', 'expired@example.com')->update(['expires_at' => now()->subMinute()]);

        $this->from('/verify')->post('/verify', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_code_cannot_be_used_twice(): void
    {
        $code = $this->requestCode('once@example.com');
        $this->post('/verify', ['code' => $code])->assertRedirect(route('dashboard'));
        $this->post('/logout');

        $this->withSession(['otp.email' => 'once@example.com'])
            ->from('/verify')->post('/verify', ['code' => $code])
            ->assertSessionHasErrors('code');
    }

    public function test_resend_is_blocked_for_sixty_seconds(): void
    {
        $this->requestCode('resend@example.com');

        $this->from('/verify')->post('/verify/resend')->assertSessionHasErrors('code');

        LoginCode::where('email', 'resend@example.com')->update(['created_at' => now()->subSeconds(61)]);
        $this->from('/verify')->post('/verify/resend')->assertSessionHas('status');
        Mail::assertSent(LoginCodeMail::class, 2);
    }

    public function test_verify_page_without_email_redirects_to_login(): void
    {
        $this->get('/verify')->assertRedirect(route('login'));
    }

    public function test_password_login_still_works(): void
    {
        User::create(['name' => 'Pw', 'username' => 'pw.user', 'email' => 'pw.user@example.com', 'password' => Hash::make('Secret123')]);

        $this->post('/login/password', ['login' => 'pw.user', 'password' => 'Secret123'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }
}
