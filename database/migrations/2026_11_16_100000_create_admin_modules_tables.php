<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Step 11: admin-managed themes, plans, subscriptions, payments, settings and link moderation.
 * Themes and plans are seeded from the values that used to live in config files.
 */
class CreateAdminModulesTables extends Migration
{
    public function up()
    {
        Schema::table('links', function (Blueprint $table) {
            $table->boolean('is_blocked')->default(false)->after('is_active')->index();
            $table->string('blocked_reason', 200)->nullable()->after('is_blocked');
        });

        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('key', 30)->unique();
            $table->string('name', 40);
            $table->string('bg', 200);
            $table->string('text', 7);
            $table->string('button', 7);
            $table->string('button_text', 7);
            $table->string('font', 20);
            $table->string('button_style', 20);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_pro')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('key', 30)->unique();
            $table->string('name', 40);
            $table->string('tagline', 120)->nullable();
            $table->unsignedInteger('price_monthly')->default(0); // INR
            $table->unsignedInteger('price_yearly')->default(0);
            $table->json('features')->nullable();                 // [[text, included], ...]
            $table->string('cta', 40)->default('Get started');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('active')->index(); // active | cancelled | expired
            $table->string('billing_cycle', 10)->default('monthly');  // monthly | yearly | lifetime
            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('ends_at')->nullable();
            $table->string('source', 20)->default('manual');          // manual | gateway
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('amount');                         // INR, whole rupees
            $table->string('currency', 3)->default('INR');
            $table->string('method', 30)->default('manual');           // manual | upi | card | netbanking | cash
            $table->string('status', 20)->default('paid')->index();    // paid | pending | refunded | failed
            $table->string('reference', 100)->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        $now = now();

        $sort = 0;
        foreach (config('linkora_design.themes') as $key => $t) {
            DB::table('themes')->insert([
                'key' => $key, 'name' => $t['name'], 'bg' => $t['bg'], 'text' => $t['text'], 'button' => $t['button'],
                'button_text' => $t['button_text'], 'font' => $t['font'], 'button_style' => $t['button_style'],
                'is_active' => true, 'is_pro' => false, 'sort' => $sort++, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $sort = 0;
        foreach (config('linkora.plans', []) as $p) {
            DB::table('plans')->insert([
                'key' => $p['key'], 'name' => $p['name'], 'tagline' => $p['tagline'],
                'price_monthly' => $p['monthly'], 'price_yearly' => $p['yearly'],
                'features' => json_encode($p['features']), 'cta' => $p['cta'],
                'is_featured' => $p['featured'], 'is_active' => true, 'sort' => $sort++,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('themes');
        Schema::table('links', function (Blueprint $table) {
            $table->dropIndex(['is_blocked']);
            $table->dropColumn(['is_blocked', 'blocked_reason']);
        });
    }
}
