<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review;
use App\Models\User;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reviews = Review::all();
        $users = User::all();

        if ($reviews->isEmpty() || $users->isEmpty()) {
            return;
        }

        foreach ($reviews as $review) {
            // 投稿者以外のユーザーから0〜3人を選ぶ
            $otherUsers = $users->where('id', '!=', $review->user_id);

            if ($otherUsers->isNotEmpty()) {
                $likeCount = min(rand(0, 3), $otherUsers->count());
                if ($likeCount > 0) {
                    $likers = $otherUsers->random($likeCount);
                    $review->likes()->syncWithoutDetaching($likers->pluck('id'));
                }
            }
        }
    }
}