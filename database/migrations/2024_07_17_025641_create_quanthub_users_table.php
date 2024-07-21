<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuanthubUsersTable extends Migration
{
    public function up() {
        Schema::create('quanthub_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('auth0_id', 100)->unique();
            $table->string('username', 100);
            $table->string('password', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('phone_number', 100)->nullable();
            $table->string('role', 100)->nullable();
            $table->string('avatar_link', 255)->nullable();
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('quanthub_users');
    }
}
