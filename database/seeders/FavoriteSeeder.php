<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Book;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        if ($users->isEmpty() || $books->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            // 各ユーザーに3〜5冊のお気に入りを設定
            $favoriteBooks = $books->random(rand(3, 5));
            $user->favorites()->syncWithoutDetaching($favoriteBooks->pluck('id'));
        }
    }
}