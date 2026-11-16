<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use DatabaseTransactions;

    private function fakeGoogleUser(string $id, string $email, string $name): void
    {
        $googleUser = (new SocialiteUser())->map([
            'id' => $id, 'email' => $email, 'name' => $name, 'avatar' => 'https://example.com/a.png',
        ]);

        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->andReturn($googleUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_new_google_user_gets_an_account_and_username(): void
    {
        $this->fakeGoogleUser('g-test-1', 'gtest.person@gmail.com', 'G Test Person');

        $this->get('/auth/google/callback?code=x')->assertRedirect(route('dashboard'));

        $user = User::where('email', 'gtest.person@gmail.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('gtest.person', $user->username);
        $this->assertSame('g-test-1', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_username_clash_gets_a_number_suffix(): void
    {
        User::create(['name' => 'Taken', 'username' => 'gtest.person', 'email' => 'other@linkora.test', 'password' => Hash::make('x')]);
        $this->fakeGoogleUser('g-test-2', 'gtest.person@yahoo.com', 'Another');

        $this->get('/auth/google/callback?code=x')->assertRedirect(route('dashboard'));

        $this->assertSame('gtest.person2', User::where('email', 'gtest.person@yahoo.com')->value('username'));
    }

    public function test_existing_email_account_is_linked_not_duplicated(): void
    {
        $existing = User::create(['name' => 'Existing', 'username' => 'existing.one', 'email' => 'existing.one@gmail.com', 'password' => Hash::make('x')]);
        $this->fakeGoogleUser('g-test-3', 'existing.one@gmail.com', 'Existing');

        $this->get('/auth/google/callback?code=x')->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($existing);
        $this->assertSame('g-test-3', $existing->fresh()->google_id);
        $this->assertSame(1, User::where('email', 'existing.one@gmail.com')->count());
    }

    public function test_suspended_account_cannot_sign_in_with_google(): void
    {
        $user = User::create(['name' => 'Sus', 'username' => 'sus.user', 'email' => 'sus.user@gmail.com', 'password' => Hash::make('x')]);
        $user->forceFill(['status' => User::STATUS_SUSPENDED])->save();
        $this->fakeGoogleUser('g-test-4', 'sus.user@gmail.com', 'Sus');

        $this->get('/auth/google/callback?code=x')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_cancelled_google_sign_in_returns_to_login(): void
    {
        $this->get('/auth/google/callback?error=access_denied')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
    }
}
