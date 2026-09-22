<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReadingPlanSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        if ($users->isEmpty() || $books->count() < 4) {
            return;
        }

        $user = $users->first();

        DB::table('reading_plans')->insert([
            [
                'user_id' => $user->id,
                'book_id' => $books[0]->id,
                'target_date' => now()->addDays(14)->toDateString(),
                'status' => 'planned',
                'completed_at' => null,
                'reminded_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'book_id' => $books[1]->id,
                'target_date' => now()->addDays(3)->toDateString(),
                'status' => 'planned',
                'completed_at' => null,
                'reminded_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'book_id' => $books[2]->id,
                'target_date' => now()->subDays(2)->toDateString(),
                'status' => 'completed',
                'completed_at' => now()->subDays(3),
                'reminded_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'book_id' => $books[3]->id,
                'target_date' => now()->subDay()->toDateString(),
                'status' => 'expired',
                'completed_at' => null,
                'reminded_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}