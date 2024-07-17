<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuanthubUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('quanthub_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('auth0Id', 100)->collation('utf8mb4_general_ci')->unique();
            $table->string('username', 100)->collation('utf8mb4_general_ci');
            $table->string('password', 100)->collation('utf8mb4_general_ci');
            $table->string('email', 100)->collation('utf8mb4_general_ci')->nullable();
            $table->string('phone_number', 100)->collation('utf8mb4_general_ci')->nullable();
            $table->string('role', 100)->collation('utf8mb4_general_ci')->nullable();
            $table->string('avatarLink', 255)->collation('utf8mb4_general_ci')->nullable();
            $table->string('created_by', 100)->collation('utf8mb4_general_ci')->nullable();
            $table->string('updated_by', 100)->collation('utf8mb4_general_ci')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('quanthub_users');
    }
}
