<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Optional background photo for the public page (Appearance → Background image). */
class AddBackgroundImageToProfiles extends Migration
{
    public function up()
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('bg_image_path')->nullable()->after('avatar_path');
        });
    }

    public function down()
    {
        Schema::table('profiles', fn (Blueprint $t) => $t->dropColumn('bg_image_path'));
    }
}
