<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            // レビューを書いたユーザー
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // レビュー対象の本
            $table->foreignId('book_id')
                ->constrained()
                ->cascadeOnDelete();

            // 評価（1〜5）
            $table->unsignedTinyInteger('rating');

            // レビュー本文
            $table->text('comment');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
