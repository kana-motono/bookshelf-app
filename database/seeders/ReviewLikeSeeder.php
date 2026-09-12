<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    public function run(): void
    {
        $reviews = Review::all();
        $users = User::all();

        if ($reviews->isEmpty() || $users->isEmpty()) {
            return;
        }

        foreach ($reviews as $review) {
            // 投稿者以外のユーザーから0〜3人を選ぶ
            $otherUsers = $users->where(
                'id',
                '!=',
                $review->user_id
            );

            if ($otherUsers->isNotEmpty()) {
                $likeCount = min(
                    rand(0, 3),
                    $otherUsers->count()
                );

                if ($likeCount > 0) {
                    $likers = $otherUsers->random($likeCount);

                    $review->likedByUsers()
                        ->syncWithoutDetaching(
                            $likers->pluck('id')
                        );
                }
            }
        }
    }
}