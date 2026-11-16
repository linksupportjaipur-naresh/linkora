<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileModulesTest extends TestCase
{
    use DatabaseTransactions;

    private const BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36';

    private function makeUser(string $username, bool $published = true): User
    {
        $user = User::create(['name' => ucfirst($username) . ' Test', 'username' => $username, 'email' => $username . '@example.com', 'password' => Hash::make('x')]);
        $user->forceFill(['role' => 'user', 'status' => 'active'])->save();
        $user->profileRecord()->forceFill(['is_published' => $published])->save();

        return $user->fresh();
    }

    // ---- Links ---------------------------------------------------------

    public function test_user_can_add_edit_toggle_reorder_and_delete_links(): void
    {
        $user = $this->makeUser('linker');
        $this->actingAs($user);

        $a = $this->postJson('/dashboard/links', ['title' => 'My shop', 'url' => 'shop.example.com'])->assertCreated()->json('link');
        $this->assertSame('https://shop.example.com', $a['url']);
        $b = $this->postJson('/dashboard/links', ['title' => 'YouTube', 'url' => 'https://youtube.com/@me'])->assertCreated()->json('link');
        $this->assertSame('fa-brands fa-youtube', $b['iconClass'], 'icon is guessed from the domain');

        // New links go on top
        $this->assertSame([$b['id'], $a['id']], $user->links()->ordered()->pluck('id')->all());

        $this->putJson("/dashboard/links/{$a['id']}", ['title' => 'Shop', 'url' => 'https://shop.example.com', 'icon' => 'shop'])->assertOk();
        $this->assertSame('shop', Link::find($a['id'])->icon);

        $this->patchJson("/dashboard/links/{$a['id']}/toggle", ['is_active' => 0])->assertOk();
        $this->assertFalse(Link::find($a['id'])->is_active);

        $this->postJson('/dashboard/links/reorder', ['order' => [$a['id'], $b['id']]])->assertOk();
        $this->assertSame([$a['id'], $b['id']], $user->links()->ordered()->pluck('id')->all());

        $this->deleteJson("/dashboard/links/{$b['id']}")->assertOk();
        $this->assertNull(Link::find($b['id']));
    }

    public function test_invalid_urls_are_rejected(): void
    {
        $this->actingAs($this->makeUser('validator'));

        foreach (['javascript:alert(1)', 'ftp://files.example.com', 'not a url', ''] as $bad) {
            $this->postJson('/dashboard/links', ['title' => 'Bad', 'url' => $bad])->assertStatus(422)->assertJsonValidationErrors('url');
        }
        $this->postJson('/dashboard/links', ['title' => '', 'url' => 'https://ok.com'])->assertJsonValidationErrors('title');
    }

    public function test_user_cannot_touch_another_users_links(): void
    {
        $owner = $this->makeUser('owner');
        $link = $owner->links()->create(['title' => 'Mine', 'url' => 'https://mine.com', 'position' => 0]);

        $this->actingAs($this->makeUser('intruder'));
        $this->putJson("/dashboard/links/{$link->id}", ['title' => 'Hacked', 'url' => 'https://evil.com'])->assertNotFound();
        $this->patchJson("/dashboard/links/{$link->id}/toggle", ['is_active' => 0])->assertNotFound();
        $this->deleteJson("/dashboard/links/{$link->id}")->assertNotFound();
        $this->postJson('/dashboard/links/reorder', ['order' => [$link->id]])->assertNotFound();

        $this->assertSame('Mine', $link->fresh()->title);
        $this->assertTrue($link->fresh()->is_active);
    }

    // ---- Profile -------------------------------------------------------

    public function test_profile_update_publish_and_unpublish(): void
    {
        $user = $this->makeUser('profiler', false);
        $this->actingAs($user);

        $this->post('/dashboard/profile', [
            'display_name' => 'Naresh Kumar', 'username' => 'Naresh', 'bio' => 'Hello there',
            'website' => 'naresh.dev', 'socials' => ['instagram' => 'instagram.com/naresh'], 'action' => 'publish',
        ])->assertRedirect('/dashboard/profile')->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('naresh', $user->username);
        $this->assertSame('Naresh Kumar', $user->profile->display_name);
        $this->assertSame('https://naresh.dev', $user->profile->website);
        $this->assertSame('https://instagram.com/naresh', $user->profile->socials['instagram']);
        $this->assertTrue($user->profile->is_published);

        $this->post('/dashboard/profile/unpublish')->assertRedirect();
        $this->assertFalse($user->profile->fresh()->is_published);
    }

    public function test_username_must_be_unique_and_not_reserved(): void
    {
        $this->makeUser('taken');
        $this->actingAs($this->makeUser('someone'));

        $this->post('/dashboard/profile', ['display_name' => 'X', 'username' => 'taken'])->assertSessionHasErrors('username');
        $this->post('/dashboard/profile', ['display_name' => 'X', 'username' => 'dashboard'])->assertSessionHasErrors('username');
        $this->post('/dashboard/profile', ['display_name' => 'X', 'username' => 'a'])->assertSessionHasErrors('username');
        $this->getJson('/dashboard/profile/check-username?username=taken')->assertJson(['available' => false]);
        $this->getJson('/dashboard/profile/check-username?username=someone')->assertJson(['available' => true]);
    }

    public function test_profile_fields_are_validated(): void
    {
        $user = $this->makeUser('validme');
        $this->actingAs($user);
        $base = ['display_name' => 'Valid Name', 'username' => 'validme'];

        // Each bad value is rejected on its own field
        foreach ([
            'display_name'      => ['display_name' => '   '],
            'display_name '     => ['display_name' => 'A'],
            'contact_email'     => ['contact_email' => 'not-an-email'],
            'phone'             => ['phone' => '12-34'],
            'phone '            => ['phone' => 'call me'],
            'website'           => ['website' => 'localhost'],
            'socials.instagram' => ['socials' => ['instagram' => 'https://facebook.com/someone']],
            'socials.github'    => ['socials' => ['github' => 'https://github.com']],
        ] as $field => $bad) {
            $this->post('/dashboard/profile', $bad + $base)->assertSessionHasErrors(trim($field));
        }

        // Good values pass, and handy shortcuts become full links
        $this->post('/dashboard/profile', $base + [
            'contact_email' => 'hello@validme.in',
            'phone'         => '+91 98765 43210',
            'website'       => 'validme.in',
            'location'      => 'Jaipur, Rajasthan',
            'socials'       => ['instagram' => '@valid.me', 'whatsapp' => '+91 98765 43210', 'youtube' => 'youtube.com/@validme'],
        ])->assertSessionHasNoErrors();

        $profile = $user->fresh()->profileRecord();
        $this->assertSame('https://validme.in', $profile->website);
        $this->assertSame('https://instagram.com/valid.me', $profile->socials['instagram']);
        $this->assertSame('https://wa.me/919876543210', $profile->socials['whatsapp']);
        $this->assertSame('https://youtube.com/@validme', $profile->socials['youtube']);

        // Location is free text: anything up to 80 characters is accepted
        foreach (['J', '📍 Jaipur | Raj @ home <3', 'C-Scheme #12, Jaipur (Raj.)'] as $loc) {
            $this->post('/dashboard/profile', $base + ['location' => $loc])->assertSessionHasNoErrors();
            $this->assertSame($loc, $user->fresh()->profileRecord()->location);
        }
        $this->post('/dashboard/profile', $base + ['location' => str_repeat('a', 81)])->assertSessionHasErrors('location');
    }

    // ---- Public page + analytics --------------------------------------

    public function test_public_page_visibility(): void
    {
        $live = $this->makeUser('livepage');
        $live->links()->create(['title' => 'Visible link', 'url' => 'https://a.com', 'position' => 0]);
        $live->links()->create(['title' => 'Hidden link', 'url' => 'https://b.com', 'position' => 1, 'is_active' => false]);
        $hidden = $this->makeUser('draftpage', false);

        $this->get('/livepage')->assertOk()->assertSee('Visible link')->assertDontSee('Hidden link');
        $this->get('/draftpage')->assertNotFound();
        $this->get('/nobody-here')->assertNotFound();

        $this->actingAs($hidden)->get('/draftpage')->assertOk()->assertSee('Only you can see this page');
    }

    public function test_views_and_clicks_are_recorded_once_and_owner_is_ignored(): void
    {
        $owner = $this->makeUser('tracked');
        $link = $owner->links()->create(['title' => 'Go', 'url' => 'https://dest.example.com', 'position' => 0]);

        $this->withHeaders(['User-Agent' => self::BROWSER, 'Referer' => 'https://www.instagram.com/'])->get('/tracked')->assertOk();
        $this->withHeaders(['User-Agent' => self::BROWSER])->get('/tracked')->assertOk(); // same session within 30 min
        $this->assertSame(1, \App\Models\ProfileView::where('user_id', $owner->id)->count());
        $this->assertDatabaseHas('profile_views', ['user_id' => $owner->id, 'referrer' => 'instagram.com', 'device' => 'desktop']);

        $this->withHeaders(['User-Agent' => self::BROWSER])->get("/l/{$link->id}")->assertRedirect('https://dest.example.com');
        $this->assertSame(1, $link->fresh()->clicks);
        $this->assertDatabaseHas('link_clicks', ['link_id' => $link->id, 'referrer' => 'instagram.com']);

        // Bots and the owner are not counted
        $this->withHeaders(['User-Agent' => 'Googlebot/2.1'])->get("/l/{$link->id}");
        $this->actingAs($owner)->withHeaders(['User-Agent' => self::BROWSER])->get("/l/{$link->id}");
        $this->assertSame(1, $link->fresh()->clicks);

        // Analytics page shows the numbers
        $this->actingAs($owner)->get('/dashboard/analytics?range=30')->assertOk()->assertSee('instagram.com');
    }

    public function test_public_page_shows_headline_link_descriptions_and_share_card(): void
    {
        $user = $this->makeUser('showcase');
        $this->actingAs($user);
        $this->post('/dashboard/profile', ['display_name' => 'Show Case', 'username' => 'showcase', 'headline' => 'Developer | Creator | Blogger'])->assertSessionHasNoErrors();
        $this->postJson('/dashboard/links', ['title' => 'Visit My Website', 'subtitle' => 'Explore more about me', 'url' => 'https://example.com', 'icon' => 'globe'])->assertCreated()
            ->assertJsonPath('link.subtitle', 'Explore more about me');
        $this->postJson('/dashboard/links', ['title' => 'x', 'subtitle' => str_repeat('a', 101), 'url' => 'https://example.com'])->assertJsonValidationErrors('subtitle');

        auth()->logout();
        $this->get('/showcase')->assertOk()
            ->assertSee('Developer | Creator | Blogger')
            ->assertSee('Explore more about me')
            ->assertSee('Share My Profile')
            ->assertSee('data-lp-qr="' . $user->publicLink() . '"', false)
            ->assertSee('Thanks for visiting!');
    }

    public function test_emoji_are_saved_and_shown(): void
    {
        $user = $this->makeUser('emojiuser');
        $this->actingAs($user);

        $this->post('/dashboard/profile', ['display_name' => 'Asha ✨', 'username' => 'emojiuser', 'headline' => 'Designer 🎨 | Creator 🚀', 'bio' => 'Hello from Jaipur 🏰 — say hi 👋🏽'])
            ->assertSessionHasNoErrors();
        $this->postJson('/dashboard/links', ['title' => 'My Shop 🛍️', 'subtitle' => 'Best deals 🔥', 'url' => 'https://shop.example.com'])->assertCreated();

        $p = $user->profile->fresh();
        $this->assertSame('Designer 🎨 | Creator 🚀', $p->headline);
        $this->assertSame('Hello from Jaipur 🏰 — say hi 👋🏽', $p->bio);

        auth()->logout();
        $this->get('/emojiuser')->assertOk()->assertSee('Asha ✨')->assertSee('Designer 🎨 | Creator 🚀')->assertSee('My Shop 🛍️')->assertSee('Best deals 🔥');

        // A full-length bio made of emoji still fits (limits count characters, not bytes)
        $this->actingAs($user);
        $this->post('/dashboard/profile', ['display_name' => 'Asha', 'username' => 'emojiuser', 'bio' => str_repeat('😀', 300)])->assertSessionHasNoErrors();
        $this->post('/dashboard/profile', ['display_name' => 'Asha', 'username' => 'emojiuser', 'bio' => str_repeat('😀', 301)])->assertSessionHasErrors('bio');
    }

    public function test_disabled_link_does_not_redirect_for_visitors(): void
    {
        $owner = $this->makeUser('offlink');
        $link = $owner->links()->create(['title' => 'Off', 'url' => 'https://x.com', 'position' => 0, 'is_active' => false]);

        $this->get("/l/{$link->id}")->assertNotFound();
    }

    // ---- Appearance ---------------------------------------------------

    public function test_appearance_saves_only_overrides_and_rejects_bad_colors(): void
    {
        $user = $this->makeUser('designer');
        $this->actingAs($user);

        $this->post('/dashboard/appearance', ['theme' => 'midnight', 'font' => 'space', 'button_style' => 'rounded', 'button_color' => '#FF0000'])
            ->assertRedirect('/dashboard/appearance');
        $p = $user->profile->fresh();
        $this->assertSame('midnight', $p->theme);
        $this->assertSame(['button_color' => '#FF0000'], $p->design);

        $this->post('/dashboard/appearance', ['theme' => 'midnight', 'font' => 'space', 'button_style' => 'rounded', 'bg_color' => 'red;}body{x'])
            ->assertSessionHasErrors('bg_color');
        $this->post('/dashboard/appearance', ['theme' => 'nope', 'font' => 'space', 'button_style' => 'rounded'])->assertSessionHasErrors('theme');

        $this->get('/designer')->assertOk()->assertSee('--lp-btn:#FF0000', false);
    }

    public function test_background_image_upload_tint_and_remove(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $user = $this->makeUser('bgphoto');
        $this->actingAs($user);
        $base = ['theme' => 'lavender', 'font' => 'instrument', 'button_style' => 'pill'];

        // Wrong type / too small are rejected
        $this->post('/dashboard/appearance', $base + ['bg_image' => \Illuminate\Http\UploadedFile::fake()->create('notes.pdf', 50, 'application/pdf')])->assertSessionHasErrors('bg_image');
        $this->post('/dashboard/appearance', $base + ['bg_image' => \Illuminate\Http\UploadedFile::fake()->image('tiny.jpg', 100, 100)])->assertSessionHasErrors('bg_image');

        // A big photo is stored, scaled down to fit 1200×2000, and shown with the chosen tint
        $this->post('/dashboard/appearance', $base + ['bg_image' => \Illuminate\Http\UploadedFile::fake()->image('beach.png', 2400, 1600), 'bg_overlay' => 'dark'])
            ->assertSessionHasNoErrors();
        $profile = $user->profile->fresh();
        $this->assertNotNull($profile->bg_image_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($profile->bg_image_path);
        [$w, $h] = getimagesizefromstring(\Illuminate\Support\Facades\Storage::disk('public')->get($profile->bg_image_path));
        $this->assertSame([1200, 800], [$w, $h]);
        $this->assertSame('dark', $profile->design['bg_overlay']);

        auth()->logout();
        $this->get('/bgphoto')->assertSee('lp-has-photo lp-tint-dark', false)->assertSee('--lp-photo: url(', false);

        // Saving without a new file keeps the photo; "remove" deletes it
        $this->actingAs($user)->post('/dashboard/appearance', $base + ['bg_overlay' => 'light']);
        $this->assertSame($profile->bg_image_path, $user->profile->fresh()->bg_image_path);

        // The default "Theme" tint lays the chosen theme over the photo, so switching themes is visible
        $this->post('/dashboard/appearance', ['theme' => 'ocean', 'font' => 'nunito', 'button_style' => 'rounded', 'bg_overlay' => 'theme'])->assertSessionHasNoErrors();
        auth()->logout();
        $this->get('/bgphoto')->assertSee('lp-has-photo lp-tint-theme', false)->assertSee('--lp-bg:linear-gradient(170deg,#0F4C75', false);
        $this->actingAs($user);

        $this->post('/dashboard/appearance', $base + ['remove_bg_image' => 1]);
        $this->assertNull($user->profile->fresh()->bg_image_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($profile->bg_image_path);
    }

    public function test_appearance_save_can_continue_to_same_site_page_only(): void
    {
        $this->actingAs($this->makeUser('nexter'));
        $base = ['theme' => 'lavender', 'font' => 'instrument', 'button_style' => 'pill'];

        $this->post('/dashboard/appearance', $base + ['next' => '/dashboard/links'])->assertRedirect('/dashboard/links');
        foreach (['https://evil.example', '//evil.example', '/\\evil.example', 'javascript:alert(1)'] as $bad) {
            $this->post('/dashboard/appearance', $base + ['next' => $bad])->assertRedirect('/dashboard/appearance');
        }
    }

    public function test_new_fonts_can_be_chosen_and_load_on_public_page(): void
    {
        $user = $this->makeUser('fontuser');
        $this->actingAs($user);

        foreach (['mukta' => 'Mukta', 'pacifico' => 'Pacifico', 'merriweather' => 'Merriweather'] as $key => $family) {
            $this->post('/dashboard/appearance', ['theme' => 'lavender', 'font' => $key, 'button_style' => 'pill'])->assertSessionHasNoErrors();
            $this->get('/fontuser')->assertSee('fonts.googleapis.com/css2?family=' . str_replace(' ', '+', $family), false)
                ->assertSee('--lp-font:"' . $family . '"'); // escaped inside the style attribute
        }
        $this->post('/dashboard/appearance', ['theme' => 'lavender', 'font' => 'comic-sans', 'button_style' => 'pill'])->assertSessionHasErrors('font');
        $this->get('/dashboard/appearance')->assertSee('Hindi + English')->assertSee('Dancing Script');
    }

    public function test_all_dashboard_pages_render(): void
    {
        $user = $this->makeUser('pages');
        $user->links()->create(['title' => 'One', 'url' => 'https://one.com', 'position' => 0]);
        $this->actingAs($user);

        foreach (['/dashboard', '/dashboard/profile', '/dashboard/links', '/dashboard/appearance', '/dashboard/analytics', '/dashboard/analytics?range=30', '/dashboard/qr-code', '/dashboard/settings'] as $url) {
            $this->get($url)->assertOk();
        }

        // The Appearance phone preview leaves out the "Share My Profile" box; the public page keeps it.
        $this->get('/dashboard/appearance')->assertDontSee('lp-sharecard', false);
        $this->get('/pages?preview=1')->assertDontSee('lp-sharecard', false); // My Profile / Manage Links phone preview
        $this->get('/pages')->assertSee('lp-sharecard', false);
    }

    public function test_passwordless_user_can_set_password(): void
    {
        $user = $this->makeUser('nopass');
        $this->actingAs($user);

        $this->post('/dashboard/settings/password', ['password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewPass123', $user->fresh()->password));

        // Now the current password is required
        $this->post('/dashboard/settings/password', ['password' => 'Other1234', 'password_confirmation' => 'Other1234'])->assertSessionHasErrors('current_password');
    }
}
