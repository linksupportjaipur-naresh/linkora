<?php

namespace Tests\Feature;

use App\Mail\LoginCodeMail;
use App\Models\Enquiry;
use App\Models\Link;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Step 12 — release checks: auth, permissions, account settings, admin panel,
 * moderation, plans/themes, error handling and security headers.
 */
class LaunchTest extends TestCase
{
    use DatabaseTransactions;

    private function user(string $username, array $attrs = [], ?string $password = null): User
    {
        $u = User::create(['name' => ucfirst($username), 'username' => $username, 'email' => $username . '@example.com', 'password' => Hash::make($password ?: 'x')]);
        $u->forceFill(array_merge(['role' => 'user', 'status' => 'active', 'password_set_at' => $password ? now() : null], $attrs))->save();
        $u->profileRecord()->forceFill(['is_published' => true])->save();

        return $u->fresh();
    }

    private function admin(): User
    {
        return $this->user('boss', ['role' => 'admin'], 'AdminPass123');
    }

    // ---------------------------------------------------------------- access

    public function test_guests_are_sent_to_login_and_users_cannot_open_admin(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/dashboard/links')->assertRedirect('/login');
        // /admin is the admin sign-in page; other admin pages send guests there
        $this->get('/admin')->assertOk()->assertSee('Admin sign in');
        $this->get('/admin/users')->assertRedirect('/admin');

        $this->actingAs($this->user('plainuser'));
        foreach (['/admin', '/admin/users', '/admin/links', '/admin/themes', '/admin/plans', '/admin/payments', '/admin/settings'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->post('/admin/settings', ['support_email' => 'x@y.com'])->assertForbidden();
    }

    public function test_admin_signs_in_at_admin_url_and_members_cannot(): void
    {
        $this->user('memberx', [], 'MemberPass123');
        $this->admin();

        // A member's correct password is still refused here (same message as a wrong password)
        $this->post('/admin', ['login' => 'memberx', 'password' => 'MemberPass123'])->assertSessionHasErrors('login');
        $this->assertGuest();
        $this->post('/admin', ['login' => 'boss', 'password' => 'wrong'])->assertSessionHasErrors('login');
        $this->assertGuest();

        // Admin signs in with username or email, lands on the dashboard, and logs out back to /admin
        $this->post('/admin', ['login' => 'boss@example.com', 'password' => 'AdminPass123'])->assertRedirect('/admin');
        $this->assertAuthenticated();
        $this->get('/admin')->assertOk()->assertSee('Growth');
        $this->post('/logout')->assertRedirect('/admin');
        $this->assertGuest();

        // Coming from a deeper admin page returns there after signing in
        $this->get('/admin/users')->assertRedirect('/admin');
        $this->post('/admin', ['login' => 'boss', 'password' => 'AdminPass123'])->assertRedirect('/admin/users');
    }

    public function test_admin_sign_in_is_rate_limited(): void
    {
        $this->admin();
        \Illuminate\Support\Facades\RateLimiter::clear('admin-login|boss|127.0.0.1');
        foreach (range(1, 5) as $i) {
            $this->post('/admin', ['login' => 'boss', 'password' => 'nope' . $i]);
        }
        $this->post('/admin', ['login' => 'boss', 'password' => 'AdminPass123'])->assertSessionHasErrors('login');
        $this->assertGuest();
        \Illuminate\Support\Facades\RateLimiter::clear('admin-login|boss|127.0.0.1');
    }

    public function test_every_admin_page_renders(): void
    {
        $member = $this->user('member');
        $link = $member->links()->create(['title' => 'Site', 'url' => 'https://a.com', 'position' => 0]);
        $enquiry = Enquiry::create(['name' => 'Asha', 'email' => 'asha@example.com', 'subject' => 'Feedback', 'message' => 'Hello there team']);
        $this->actingAs($this->admin());

        $pages = ['/admin', '/admin/users', '/admin/users?q=member&status=active', "/admin/users/{$member->id}", "/admin/users/{$member->id}/edit",
            '/admin/profiles', '/admin/profiles?state=published', '/admin/links', '/admin/links?state=blocked', '/admin/themes', '/admin/themes/create',
            '/admin/themes/' . Theme::first()->id . '/edit', '/admin/plans', '/admin/plans/create', '/admin/plans/' . Plan::first()->id . '/edit',
            '/admin/subscriptions', '/admin/payments', '/admin/enquiries', "/admin/enquiries/{$enquiry->id}", '/admin/settings'];
        foreach ($pages as $url) {
            $this->get($url)->assertOk();
        }
        $this->assertSame('read', $enquiry->fresh()->status, 'opening an enquiry marks it read');
    }

    // ---------------------------------------------------------------- auth

    public function test_password_reset_flow(): void
    {
        Notification::fake();
        $user = $this->user('forgetful', [], 'OldPass123');

        $this->post('/forgot-password', ['email' => 'forgetful@example.com'])->assertSessionHas('status');
        $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertSessionHas('status'); // same message, no leak

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($n) use (&$token) { $token = $n->token; return true; });

        $this->post('/reset-password', ['token' => $token, 'email' => 'forgetful@example.com', 'password' => 'NewPass456', 'password_confirmation' => 'NewPass456'])
            ->assertRedirect('/login');
        $this->post('/login/password', ['login' => 'forgetful', 'password' => 'NewPass456'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected_and_rate_limited(): void
    {
        $this->user('guarded', [], 'RightPass123');
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login/password', ['login' => 'guarded', 'password' => 'wrong'])->assertSessionHasErrors('login');
        }
        $this->post('/login/password', ['login' => 'guarded', 'password' => 'RightPass123'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    // ---------------------------------------------------------------- account settings (step 10)

    public function test_settings_photo_upload_and_remove(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $user = $this->user('photoguy');
        $this->actingAs($user);

        $this->get('/dashboard/settings')->assertOk()->assertSee('Profile photo')->assertSee('Upload photo');

        // Bad files are refused
        $this->post('/dashboard/settings/photo', [])->assertSessionHasErrors('avatar');
        $this->post('/dashboard/settings/photo', ['avatar' => \Illuminate\Http\UploadedFile::fake()->create('cv.pdf', 40, 'application/pdf')])->assertSessionHasErrors('avatar');
        $this->post('/dashboard/settings/photo', ['avatar' => \Illuminate\Http\UploadedFile::fake()->image('tiny.png', 60, 60)])->assertSessionHasErrors('avatar');

        // A good photo is saved as a 400×400 JPEG on the profile, replacing the old one
        $this->post('/dashboard/settings/photo', ['avatar' => \Illuminate\Http\UploadedFile::fake()->image('me.png', 640, 640)])->assertSessionHasNoErrors();
        $first = $user->fresh()->profileRecord()->avatar_path;
        $this->assertNotNull($first);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($first);
        $this->assertSame([400, 400], array_slice(getimagesizefromstring(\Illuminate\Support\Facades\Storage::disk('public')->get($first)), 0, 2));

        $this->post('/dashboard/settings/photo', ['avatar' => \Illuminate\Http\UploadedFile::fake()->image('me2.jpg', 800, 600)])->assertSessionHasNoErrors();
        $second = $user->fresh()->profileRecord()->avatar_path;
        $this->assertNotSame($first, $second);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($first);

        // Same photo shows on Settings, My Profile and the top bar
        $this->get('/dashboard/settings')->assertSee($second)->assertSee('Change photo');
        $this->get('/dashboard/profile')->assertSee($second);

        // Remove
        $this->post('/dashboard/settings/photo/remove')->assertRedirect();
        $this->assertNull($user->fresh()->profileRecord()->avatar_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($second);
    }

    public function test_storage_route_serves_only_uploaded_images(): void
    {
        $dir = storage_path('app/public/avatars');
        @mkdir($dir, 0775, true);
        $file = $dir . '/zz-storage-route-test.png';
        file_put_contents($file, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
        file_put_contents($dir . '/zz-note.txt', 'secret');

        try {
            $this->get('/storage/avatars/zz-storage-route-test.png')->assertOk()->assertHeader('Content-Type', 'image/png');
            $this->get('/storage/avatars/missing.png')->assertNotFound();
            $this->get('/storage/avatars/zz-note.txt')->assertNotFound();          // not an image
            $this->get('/storage/../../.env')->assertNotFound();                   // can't leave the folder
            $this->get('/storage/%2e%2e/%2e%2e/.env')->assertNotFound();
        } finally {
            @unlink($file);
            @unlink($dir . '/zz-note.txt');
        }
    }

    public function test_public_path_can_point_at_public_html(): void
    {
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lk_public_html_' . uniqid();
        mkdir($tmp);
        config(['app.public_path' => $tmp]);
        (new \App\Providers\AppServiceProvider($this->app))->register();
        $this->assertSame($tmp, public_path());
        rmdir($tmp);
    }

    public function test_email_change_needs_code_from_new_address(): void
    {
        Mail::fake();
        $user = $this->user('mover');
        $this->user('taken');
        $this->actingAs($user);

        $this->post('/dashboard/settings/email', ['email' => 'taken@example.com'])->assertSessionHasErrors('email');

        $this->post('/dashboard/settings/email', ['email' => 'New.Address@Example.com'])->assertSessionHas('status');
        $this->assertSame('mover@example.com', $user->fresh()->email, 'not changed until confirmed');

        $code = null;
        Mail::assertSent(LoginCodeMail::class, function ($m) use (&$code) { $code = $m->code; return $m->hasTo('new.address@example.com') && $m->intent === 'email_change'; });

        $this->post('/dashboard/settings/email/confirm', ['email_code' => $code === '000000' ? '111111' : '000000'])->assertSessionHasErrors('email_code');
        $r = $this->post('/dashboard/settings/email/confirm', ['email_code' => $code]);
        $r->assertSessionHas('status');
        $this->assertSame('new.address@example.com', $user->fresh()->email);
    }

    public function test_right_code_still_works_after_a_wrong_attempt(): void
    {
        Mail::fake();
        $this->user('retry');
        $this->post('/auth/email', ['email' => 'retry@example.com', 'intent' => 'login']);
        $code = null;
        Mail::assertSent(LoginCodeMail::class, function ($m) use (&$code) { $code = $m->code; return true; });

        $this->from('/verify')->post('/verify', ['code' => $code === '000000' ? '111111' : '000000'])->assertSessionHasErrors('code');
        $this->post('/verify', ['code' => $code])->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = $this->user('changer', [], 'OldPass123');
        $this->actingAs($user);

        $this->post('/dashboard/settings/password', ['current_password' => 'nope', 'password' => 'NewPass456', 'password_confirmation' => 'NewPass456'])->assertSessionHasErrors('current_password');
        $this->post('/dashboard/settings/password', ['current_password' => 'OldPass123', 'password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');
        $this->post('/dashboard/settings/password', ['current_password' => 'OldPass123', 'password' => 'NewPass456', 'password_confirmation' => 'NewPass456'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewPass456', $user->fresh()->password));
    }

    public function test_deactivate_then_log_in_again_reactivates(): void
    {
        $user = $this->user('pauser', [], 'Pause1234');
        $this->actingAs($user);

        $this->post('/dashboard/settings/deactivate', ['confirm_username' => 'pauser', 'current_password' => 'wrong'])->assertSessionHasErrors('current_password_confirm');
        $this->post('/dashboard/settings/deactivate', ['confirm_username' => 'pauser', 'current_password' => 'Pause1234'])->assertRedirect('/login');
        $this->assertGuest();
        $this->assertSame('deactivated', $user->fresh()->status);
        $this->get('/pauser')->assertNotFound();

        $this->post('/login/password', ['login' => 'pauser', 'password' => 'Pause1234'])->assertRedirect('/dashboard');
        $this->assertSame('active', $user->fresh()->status);
    }

    public function test_delete_account_needs_username_and_password(): void
    {
        $user = $this->user('leaver', [], 'Leave1234');
        $user->links()->create(['title' => 'x', 'url' => 'https://x.com', 'position' => 0]);
        $this->actingAs($user);

        $this->post('/dashboard/settings/delete', ['confirm_username' => 'wrong', 'current_password' => 'Leave1234'])->assertSessionHasErrors('confirm_username');
        $this->post('/dashboard/settings/delete', ['confirm_username' => 'leaver', 'current_password' => 'Leave1234'])->assertRedirect('/');
        $this->assertNull(User::find($user->id));
        $this->assertSame(0, Link::where('user_id', $user->id)->count());
    }

    // ---------------------------------------------------------------- admin (step 11)

    public function test_admin_blocks_unblocks_and_deletes_users(): void
    {
        $target = $this->user('target', [], 'Target123');
        $admin = $this->admin();

        $this->actingAs($admin)->post("/admin/users/{$target->id}/block")->assertRedirect();
        $this->assertTrue($target->fresh()->isBlocked());

        // Blocked: public page gone, existing session kicked out, can't log in
        $this->get('/target')->assertNotFound();
        $this->actingAs($target->fresh())->get('/dashboard')->assertRedirect('/login');
        $this->post('/login/password', ['login' => 'target', 'password' => 'Target123'])->assertSessionHasErrors('login');
        Mail::fake();
        $this->post('/auth/email', ['email' => 'target@example.com', 'intent' => 'login'])->assertSessionHasErrors('email');
        Mail::assertNothingSent();

        $this->actingAs($admin)->post("/admin/users/{$target->id}/unblock");
        $this->assertTrue($target->fresh()->isActive());

        // Admins can't be blocked/deleted, and an admin can't demote themself
        $this->post("/admin/users/{$admin->id}/block")->assertSessionHas('error');
        $this->delete("/admin/users/{$admin->id}")->assertSessionHas('error');
        $this->put("/admin/users/{$admin->id}", ['name' => 'Boss', 'email' => 'boss@example.com', 'username' => 'boss', 'role' => 'user', 'status' => 'active'])->assertSessionHasErrors('role');

        $this->delete("/admin/users/{$target->id}")->assertRedirect('/admin/users');
        $this->assertNull(User::find($target->id));
    }

    public function test_admin_edits_user_with_validation(): void
    {
        $u = $this->user('editme');
        $this->user('other');
        $this->actingAs($this->admin());

        $this->put("/admin/users/{$u->id}", ['name' => 'Edited', 'email' => 'other@example.com', 'username' => 'editme', 'role' => 'user', 'status' => 'active'])->assertSessionHasErrors('email');
        $this->put("/admin/users/{$u->id}", ['name' => 'Edited', 'email' => 'edited@example.com', 'username' => 'edited.name', 'role' => 'user', 'status' => 'active'])->assertRedirect();
        $this->assertSame(['Edited', 'edited@example.com', 'edited.name'], [$u->fresh()->name, $u->fresh()->email, $u->fresh()->username]);
    }

    public function test_blocked_link_disappears_and_owner_cannot_reenable_it(): void
    {
        $owner = $this->user('linkowner');
        $link = $owner->links()->create(['title' => 'Shady deal', 'url' => 'https://shady.example', 'position' => 0]);
        $this->get('/linkowner')->assertSee('Shady deal');

        $this->actingAs($this->admin())->post("/admin/links/{$link->id}/block", ['reason' => ''])->assertSessionHasErrors('reason');
        $this->post("/admin/links/{$link->id}/block", ['reason' => 'Spam or misleading link']);
        $this->assertTrue($link->fresh()->is_blocked);

        auth()->logout();
        $this->get('/linkowner')->assertDontSee('Shady deal');
        $this->get("/l/{$link->id}")->assertNotFound();

        $this->actingAs($owner)->patchJson("/dashboard/links/{$link->id}/toggle", ['is_active' => 1])->assertForbidden();
        $this->get('/dashboard/links')->assertSee('Spam or misleading link');
    }

    public function test_admin_moderates_profiles(): void
    {
        $owner = $this->user('loud');
        $owner->profileRecord()->forceFill(['bio' => 'Something rude'])->save();
        $profile = $owner->profileRecord();
        $this->actingAs($this->admin());

        $this->post("/admin/profiles/{$profile->id}/clear", ['field' => 'bio']);
        $this->assertNull($profile->fresh()->bio);
        $this->post("/admin/profiles/{$profile->id}/unpublish");
        $this->assertFalse($profile->fresh()->is_published);
        auth()->logout();
        $this->get('/loud')->assertNotFound();
    }

    public function test_themes_admin_validation_and_pro_gating(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $base = ['name' => 'Galaxy', 'text' => '#FFFFFF', 'button' => '#B79CFF', 'button_text' => '#1B1233', 'font' => 'space', 'button_style' => 'pill', 'is_active' => 1, 'is_pro' => 1];
        $this->post('/admin/themes', $base + ['bg' => 'url(javascript:alert(1))'])->assertSessionHasErrors('bg');
        $this->post('/admin/themes', $base + ['bg' => 'linear-gradient(180deg, #1B1233 0%, #3A2A7A 100%)'])->assertRedirect('/admin/themes');
        $theme = Theme::where('name', 'Galaxy')->firstOrFail();

        // Free user can't pick a Pro theme…
        $free = $this->user('freebie');
        $this->actingAs($free)->post('/dashboard/appearance', ['theme' => $theme->key, 'font' => 'space', 'button_style' => 'pill'])->assertSessionHasErrors('theme');

        // …until an admin gives them a paid plan (with a recorded payment)
        $pro = Plan::where('key', 'pro')->firstOrFail();
        $this->actingAs($admin)->post("/admin/users/{$free->id}/plan", ['plan_id' => $pro->id, 'billing_cycle' => 'monthly', 'amount' => 149, 'method' => 'upi', 'reference' => 'UPI-123']);
        $this->assertTrue($free->fresh()->isPro());
        $this->assertDatabaseHas('payments', ['user_id' => $free->id, 'amount' => 149, 'method' => 'upi', 'status' => 'paid']);
        $this->actingAs($free->fresh())->post('/dashboard/appearance', ['theme' => $theme->key, 'font' => 'space', 'button_style' => 'pill'])->assertSessionHasNoErrors();

        // Hidden themes disappear from the picker; the default theme can't be hidden or deleted
        $this->actingAs($admin)->post("/admin/themes/{$theme->id}/toggle");
        $this->assertFalse($theme->fresh()->is_active);
        $default = Theme::where('key', config('linkora_design.default_theme'))->first();
        $this->delete("/admin/themes/{$default->id}")->assertSessionHas('error');

        // Deleting a theme moves its users to the default theme
        $this->delete("/admin/themes/{$theme->id}");
        $this->assertSame(config('linkora_design.default_theme'), $free->profileRecord()->fresh()->theme);
    }

    public function test_plan_changes_show_on_pricing_page(): void
    {
        $pro = Plan::where('key', 'pro')->firstOrFail();
        $this->actingAs($this->admin())->put("/admin/plans/{$pro->id}", [
            'name' => 'Pro', 'tagline' => 'Grow faster', 'price_monthly' => 199, 'price_yearly' => 1990,
            'features' => "Unlimited links\nAll themes\n- Team seats", 'cta' => 'Go Pro', 'is_active' => 1, 'is_featured' => 1,
        ])->assertRedirect('/admin/plans');

        $this->assertSame([['Unlimited links', true], ['All themes', true], ['Team seats', false]], $pro->fresh()->features);
        $this->get('/pricing')->assertSee('₹199')->assertSee('Grow faster');

        $free = Plan::where('key', 'free')->first();
        $this->delete("/admin/plans/{$free->id}")->assertSessionHas('error');
    }

    public function test_settings_close_registration_and_update_contact_details(): void
    {
        $this->actingAs($this->admin())->post('/admin/settings', ['support_email' => 'help@linkora.test', 'support_phone' => '+91 90000 00000', 'address' => 'Jaipur'])
            ->assertSessionHas('status'); // registration_open unchecked → closed

        $this->assertSame('0', Setting::find('registration_open')->value);

        // Re-apply settings to config, as the next request's boot would
        (new \App\Providers\AppServiceProvider($this->app))->boot();
        $this->assertFalse(config('linkora.registration_open'));
        $this->assertSame('help@linkora.test', config('linkora.support_email'));

        Mail::fake();
        auth()->logout();
        $this->post('/auth/email', ['email' => 'brandnew@example.com', 'intent' => 'register'])->assertSessionHasErrors('email');
        Mail::assertNothingSent();
        $this->get('/register')->assertSee('New sign-ups are paused');

        Setting::put(['registration_open' => '1']);
    }

    // ---------------------------------------------------------------- QR (step 9)

    public function test_qr_code_points_at_the_live_public_profile(): void
    {
        $user = $this->user('qr.owner');
        $user->profileRecord()->forceFill(['display_name' => 'QR Owner'])->save();
        $url = $user->publicLink();

        // The QR page encodes exactly the public profile URL…
        $this->actingAs($user)->get('/dashboard/qr-code')->assertOk()->assertSee(json_encode($url), false);

        // …and a logged-out phone scanning it lands on that profile.
        auth()->logout();
        $this->get(parse_url($url, PHP_URL_PATH))->assertOk()->assertSee('QR Owner')->assertSee('@qr.owner');

        // Hyphenated (admin) usernames resolve too.
        $this->user('team-lead', ['role' => 'admin']);
        $this->get('/team-lead')->assertOk()->assertSee('@team-lead');
    }

    // ---------------------------------------------------------------- errors & security

    public function test_error_pages_and_security_headers(): void
    {
        $this->get('/no/such/page')->assertNotFound()->assertSee('We can’t find that page');

        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_csrf_is_enforced_on_real_requests(): void
    {
        $user = $this->user('csrfuser');
        // Turn the test-mode CSRF bypass off for this request.
        $this->app['env'] = 'local';
        $response = $this->actingAs($user)->call('POST', '/dashboard/profile/publish', [], [], [], ['HTTP_ACCEPT' => 'text/html']);
        $this->assertContains($response->getStatusCode(), [419, 302]);
    }
}
