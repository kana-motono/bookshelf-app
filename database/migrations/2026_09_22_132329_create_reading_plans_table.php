<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reading_plans', function (Blueprint $table) {
            $table->id();

            // この読書計画を作ったユーザー
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // 読む予定の書籍
            $table->foreignId('book_id')
                ->constrained()
                ->cascadeOnDelete();

            // 読書期限
            $table->date('target_date');

            // planned: 読書予定
            // completed: 完了
            // expired: 期限切れ
            $table->string('status')->default('planned');

            // 読み終わった日時
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // 同じユーザーが同じ本の読書計画を
            // 複数作らないようにする
            $table->unique(['user_id', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_plans');
    }
};