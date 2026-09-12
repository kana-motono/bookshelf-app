<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
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

            $user->favoriteBooks()->syncWithoutDetaching(
                $favoriteBooks->pluck('id')
            );
        }
    }
}