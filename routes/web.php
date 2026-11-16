<?php

use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\EnquiryController as AdminEnquiryController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\EmailCodeController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Dashboard\AnalyticsController;
use App\Http\Controllers\Dashboard\AppearanceController;
use App\Http\Controllers\Dashboard\LinkController;
use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\SettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PublicProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/features', [PageController::class, 'features'])->name('features');
Route::get('/pricing', [PageController::class, 'pricing'])->name('pricing');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::get('/login', [LoginController::class, 'create'])->name('login');

    // Passwordless: email → 6-digit code → signed in
    Route::post('/auth/email', [EmailCodeController::class, 'send'])->middleware('throttle:6,1')->name('otp.send');
    Route::get('/verify', [EmailCodeController::class, 'show'])->name('otp.show');
    Route::post('/verify', [EmailCodeController::class, 'verify'])->middleware('throttle:15,1')->name('otp.verify');
    Route::post('/verify/resend', [EmailCodeController::class, 'resend'])->middleware('throttle:4,1')->name('otp.resend');

    // Optional: email/username + password
    Route::get('/login/password', [LoginController::class, 'createWithPassword'])->name('login.password');
    Route::post('/login/password', [LoginController::class, 'store'])->name('login.password.store');

    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| User dashboard
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('dashboard.profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('dashboard.profile.update');
    Route::post('/profile/publish', [ProfileController::class, 'publish'])->name('dashboard.profile.publish');
    Route::post('/profile/unpublish', [ProfileController::class, 'unpublish'])->name('dashboard.profile.unpublish');
    Route::get('/profile/check-username', [ProfileController::class, 'checkUsername'])->middleware('throttle:60,1')->name('dashboard.profile.username');

    Route::get('/links', [LinkController::class, 'index'])->name('dashboard.links');
    Route::post('/links', [LinkController::class, 'store'])->name('dashboard.links.store');
    Route::post('/links/reorder', [LinkController::class, 'reorder'])->name('dashboard.links.reorder');
    Route::put('/links/{id}', [LinkController::class, 'update'])->whereNumber('id')->name('dashboard.links.update');
    Route::patch('/links/{id}/toggle', [LinkController::class, 'toggle'])->whereNumber('id')->name('dashboard.links.toggle');
    Route::delete('/links/{id}', [LinkController::class, 'destroy'])->whereNumber('id')->name('dashboard.links.destroy');

    Route::get('/appearance', [AppearanceController::class, 'edit'])->name('dashboard.appearance');
    Route::post('/appearance', [AppearanceController::class, 'update'])->name('dashboard.appearance.update');

    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('dashboard.analytics');
    Route::get('/qr-code', [SettingsController::class, 'qr'])->name('dashboard.qr');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('dashboard.settings');
    Route::post('/settings/account', [SettingsController::class, 'updateAccount'])->name('dashboard.settings.account');
    Route::post('/settings/photo', [SettingsController::class, 'updatePhoto'])->middleware('throttle:20,1')->name('dashboard.settings.photo');
    Route::post('/settings/photo/remove', [SettingsController::class, 'removePhoto'])->name('dashboard.settings.photo.remove');
    Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->middleware('throttle:10,1')->name('dashboard.settings.password');
    Route::post('/settings/email', [SettingsController::class, 'requestEmailChange'])->middleware('throttle:5,1')->name('dashboard.settings.email');
    Route::post('/settings/email/confirm', [SettingsController::class, 'confirmEmailChange'])->middleware('throttle:10,1')->name('dashboard.settings.email.confirm');
    Route::post('/settings/email/cancel', [SettingsController::class, 'cancelEmailChange'])->name('dashboard.settings.email.cancel');
    Route::post('/settings/deactivate', [SettingsController::class, 'deactivate'])->middleware('throttle:5,1')->name('dashboard.settings.deactivate');
    Route::post('/settings/delete', [SettingsController::class, 'destroy'])->middleware('throttle:5,1')->name('dashboard.settings.destroy');
});

/*
|--------------------------------------------------------------------------
| Admin panel
|--------------------------------------------------------------------------
*/
// /admin is the admin sign-in page for guests and the dashboard for signed-in admins (members get 403).
Route::get('/admin', [AdminLoginController::class, 'entry'])->middleware('active')->name('admin.dashboard');
Route::post('/admin', [AdminLoginController::class, 'store'])->middleware(['guest', 'throttle:10,1'])->name('admin.login');

Route::middleware(['auth', 'active', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    // Users
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/block', [AdminUserController::class, 'block'])->name('users.block');
    Route::post('/users/{user}/unblock', [AdminUserController::class, 'unblock'])->name('users.unblock');
    Route::post('/users/{user}/plan', [AdminUserController::class, 'assignPlan'])->name('users.plan');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    // Moderation
    Route::get('/profiles', [ModerationController::class, 'profiles'])->name('profiles.index');
    Route::post('/profiles/{profile}/unpublish', [ModerationController::class, 'unpublishProfile'])->name('profiles.unpublish');
    Route::post('/profiles/{profile}/publish', [ModerationController::class, 'publishProfile'])->name('profiles.publish');
    Route::post('/profiles/{profile}/clear', [ModerationController::class, 'clearProfile'])->name('profiles.clear');
    Route::get('/links', [ModerationController::class, 'links'])->name('links.index');
    Route::post('/links/{link}/block', [ModerationController::class, 'blockLink'])->name('links.block');
    Route::post('/links/{link}/unblock', [ModerationController::class, 'unblockLink'])->name('links.unblock');
    Route::delete('/links/{link}', [ModerationController::class, 'destroyLink'])->name('links.destroy');

    // Themes & plans
    Route::get('/themes', [CatalogController::class, 'themes'])->name('themes.index');
    Route::get('/themes/create', [CatalogController::class, 'createTheme'])->name('themes.create');
    Route::post('/themes', [CatalogController::class, 'storeTheme'])->name('themes.store');
    Route::get('/themes/{theme}/edit', [CatalogController::class, 'editTheme'])->name('themes.edit');
    Route::put('/themes/{theme}', [CatalogController::class, 'updateTheme'])->name('themes.update');
    Route::post('/themes/{theme}/toggle', [CatalogController::class, 'toggleTheme'])->name('themes.toggle');
    Route::delete('/themes/{theme}', [CatalogController::class, 'destroyTheme'])->name('themes.destroy');
    Route::get('/plans', [CatalogController::class, 'plans'])->name('plans.index');
    Route::get('/plans/create', [CatalogController::class, 'createPlan'])->name('plans.create');
    Route::post('/plans', [CatalogController::class, 'storePlan'])->name('plans.store');
    Route::get('/plans/{plan}/edit', [CatalogController::class, 'editPlan'])->name('plans.edit');
    Route::put('/plans/{plan}', [CatalogController::class, 'updatePlan'])->name('plans.update');
    Route::delete('/plans/{plan}', [CatalogController::class, 'destroyPlan'])->name('plans.destroy');

    // Billing
    Route::get('/subscriptions', [BillingController::class, 'subscriptions'])->name('subscriptions.index');
    Route::post('/subscriptions/{subscription}/cancel', [BillingController::class, 'cancelSubscription'])->name('subscriptions.cancel');
    Route::get('/payments', [BillingController::class, 'payments'])->name('payments.index');
    Route::post('/payments/{payment}', [BillingController::class, 'updatePayment'])->name('payments.update');

    // Enquiries & settings
    Route::get('/enquiries', [AdminEnquiryController::class, 'index'])->name('enquiries.index');
    Route::get('/enquiries/{enquiry}', [AdminEnquiryController::class, 'show'])->name('enquiries.show');
    Route::post('/enquiries/{enquiry}', [AdminEnquiryController::class, 'update'])->name('enquiries.update');
    Route::delete('/enquiries/{enquiry}', [AdminEnquiryController::class, 'destroy'])->name('enquiries.destroy');
    Route::get('/settings', [AdminSettingController::class, 'edit'])->name('settings');
    Route::post('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
});

/*
|--------------------------------------------------------------------------
| Public profile pages — keep these LAST so they never shadow other routes
|--------------------------------------------------------------------------
*/
Route::get('/l/{link}', [PublicProfileController::class, 'click'])->whereNumber('link')->middleware('throttle:120,1')->name('link.go');
Route::get('/{username}', [PublicProfileController::class, 'show'])
    ->where('username', '[A-Za-z0-9](?:[A-Za-z0-9_.-]{1,28}[A-Za-z0-9])?')
    ->name('profile.public');
