<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Steps 3–8: profiles, links, analytics events and the activity feed.
 */
class CreateProfileModulesTables extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Set when the user chooses their own password (passwordless / Google users start without one).
            $table->timestamp('password_set_at')->nullable()->after('password');
        });

        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name', 60)->nullable();
            $table->string('bio', 300)->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('location', 80)->nullable();
            $table->json('socials')->nullable();
            $table->string('theme', 30)->default('lavender');
            $table->json('design')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 80);
            $table->string('url', 2048);
            $table->string('icon', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamps();
            $table->index(['user_id', 'position']);
        });

        Schema::create('profile_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('visitor', 40);              // hashed IP + browser + day, never the raw IP
            $table->string('referrer', 120)->nullable(); // host only, e.g. instagram.com
            $table->string('device', 10)->nullable();    // mobile | tablet | desktop
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('link_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('visitor', 40);
            $table->string('referrer', 120)->nullable();
            $table->string('device', 10)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'created_at']);
            $table->index(['link_id', 'created_at']);
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('description', 255);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('activities');
        Schema::dropIfExists('link_clicks');
        Schema::dropIfExists('profile_views');
        Schema::dropIfExists('links');
        Schema::dropIfExists('profiles');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_set_at');
        });
    }
}
