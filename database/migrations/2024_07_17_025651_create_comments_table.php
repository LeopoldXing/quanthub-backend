<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('comments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('content')->collation('utf8mb4_general_ci');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->datetime('publish_datetime')->nullable();
            $table->unsignedBigInteger('article_id')->nullable();
            $table->string('status', 100)->collation('utf8mb4_general_ci');
            $table->string('created_by', 100)->collation('utf8mb4_general_ci')->nullable();
            $table->string('updated_by', 100)->collation('utf8mb4_general_ci')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('quanthub_users')->onDelete('cascade');
            $table->foreign('article_id')->references('id')->on('articles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('comments');
    }
}
