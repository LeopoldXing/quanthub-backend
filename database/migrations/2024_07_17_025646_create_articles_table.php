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
            $table->text('content');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->decimal('rate', 3, 1)->nullable();
            $table->string('status', 100)->nullable();
            $table->string('type', 100)->nullable();
            $table->unsignedBigInteger('draft_reference_id')->nullable();
            $table->string('cover_image_link', 255)->nullable();
            $table->date('publish_date')->nullable();
            $table->string('attachment_link', 255)->nullable();
            $table->string('attachment_name', 255)->nullable();
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();

            $table->foreign('author_id')->references('id')->on('quanthub_users');
            $table->foreign('category_id')->references('id')->on('categories');
        });
    }

    public function down() {
        Schema::dropIfExists('articles');
    }
}
