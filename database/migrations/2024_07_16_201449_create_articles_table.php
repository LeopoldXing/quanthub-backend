<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticlesTable extends Migration
{
    public function up() {
        Schema::create('articles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('author_id');
            $table->string('title', 100)->nullable();
            $table->string('sub_title', 100)->nullable();
            $table->text('content')->nullable();
            $table->decimal('rate', 3, 1)->nullable();
            $table->string('status', 100)->nullable();
            $table->date('publish_date')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();

            $table->foreign('author_id')->references('id')->on('quanthub_users')->onDelete('cascade');
        });
    }

    public function down() {
        Schema::dropIfExists('articles');
    }
}
