<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // 投稿したユーザーのID
    $table->string('title'); // 本のタイトル
    $table->string('author'); // 著者名
    $table->string('isbn', 13)->nullable(); // ISBN（13桁）
    $table->date('published_at')->nullable(); // 出版日
    $table->string('image_url')->nullable(); // 画像のURL
    $table->text('description')->nullable(); // 説明文
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
