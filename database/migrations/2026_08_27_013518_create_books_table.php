<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();

            // この本を登録したユーザー
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // 書籍情報
            $table->string('title');
            $table->string('author');
            $table->string('isbn', 13)->nullable();
            $table->date('published_date')->nullable();
            $table->string('image_url')->nullable();
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};