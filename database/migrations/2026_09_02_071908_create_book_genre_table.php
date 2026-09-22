<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('book_genre', function (Blueprint $table) {
            $table->id();

            // 対象の本
            $table->foreignId('book_id')
                ->constrained()
                ->cascadeOnDelete();

            // 対象のジャンル
            $table->foreignId('genre_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            // 同じ本に同じジャンルを重複して登録できないようにする
            $table->unique(['book_id', 'genre_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_genre');
    }
};