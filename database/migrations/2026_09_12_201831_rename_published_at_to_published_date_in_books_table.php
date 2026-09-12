<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (
            Schema::hasColumn('books', 'published_at')
            && !Schema::hasColumn('books', 'published_date')
        ) {
            Schema::table('books', function (Blueprint $table) {
                $table->renameColumn('published_at', 'published_date');
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasColumn('books', 'published_date')
            && !Schema::hasColumn('books', 'published_at')
        ) {
            Schema::table('books', function (Blueprint $table) {
                $table->renameColumn('published_date', 'published_at');
            });
        }
    }
};