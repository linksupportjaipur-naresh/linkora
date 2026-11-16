<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public page redesign: a short headline under the name ("Developer | Creator | Blogger")
 * and a one-line description under each link title ("Explore more about me").
 */
class AddHeadlineAndLinkSubtitle extends Migration
{
    public function up()
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('headline', 80)->nullable()->after('display_name');
        });
        Schema::table('links', function (Blueprint $table) {
            $table->string('subtitle', 100)->nullable()->after('title');
        });
    }

    public function down()
    {
        Schema::table('profiles', fn (Blueprint $t) => $t->dropColumn('headline'));
        Schema::table('links', fn (Blueprint $t) => $t->dropColumn('subtitle'));
    }
}
