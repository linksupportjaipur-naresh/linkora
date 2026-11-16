<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccountFieldsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->unique()->after('name');
            $table->string('role', 20)->default('user')->after('password')->index();   // user | admin
            $table->string('status', 20)->default('active')->after('role');            // active | suspended
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropIndex(['role']);
            $table->dropColumn(['username', 'role', 'status', 'last_login_at']);
        });
    }
}
