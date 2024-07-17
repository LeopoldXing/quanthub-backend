<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('articles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('author_id');
            $table->string('title', 100)->collation('utf8mb4_general_ci')->nullable();
            $table->string('sub_title', 100)->collation('utf8mb4_general_ci')->nullable();
            $table->text('content')->collation('utf8mb4_general_ci');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->decimal('rate', 3, 1)->default(0);
            $table->string('status', 100)->collation('utf8mb4_general_ci')->nullable();
            $table->string('cover_image_link', 255)->collation('utf8mb4_general_ci')->nullable();
            $table->date('publish_date')->nullable();
            $table->string('attachment_link', 255)->collation('utf8mb4_general_ci')->nullable();
            $table->string('created_by', 100)->collation('utf8mb4_general_ci')->nullable();
            $table->string('updated_by', 100)->collation('utf8mb4_general_ci')->nullable();
            $table->timestamps();

            $table->foreign('author_id')->references('id')->on('quanthub_users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('articles');
    }
}
