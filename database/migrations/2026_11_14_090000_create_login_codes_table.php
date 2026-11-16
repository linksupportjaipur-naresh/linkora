<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoginCodesTable extends Migration
{
    public function up()
    {
        Schema::create('login_codes', function (Blueprint $table) {
            $table->id();
            $table->string('email', 150)->index();
            $table->string('code_hash');                     // the 6-digit code is never stored in plain text
            $table->string('intent', 20)->default('login');  // login | register
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('login_codes');
    }
}
