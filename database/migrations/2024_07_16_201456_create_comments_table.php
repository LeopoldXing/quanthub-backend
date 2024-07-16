<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommentsTable extends Migration
{
    public function up() {
        Schema::create('comments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('content')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->dateTime('publish_datetime')->nullable();
            $table->unsignedBigInteger('article_id')->nullable();
            $table->string('status', 100);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('quanthub_users')->onDelete('cascade');
            $table->foreign('article_id')->references('id')->on('articles')->onDelete('cascade');
        });
    }

    public function down() {
        Schema::dropIfExists('comments');
    }
}
