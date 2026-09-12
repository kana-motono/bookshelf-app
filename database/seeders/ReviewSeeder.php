<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review;
use App\Models\User;
use App\Models\Book;

class ReviewSeeder extends Seeder
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

        $comments = [
            5 => [
                '本当に素晴らしい名著です。何度も読み返したくなります。',//例
                '目から鱗が落ちる思いでした。人生のバイブルにします。',
                '文章が美しく、ストーリーに一気に引き込まれました。',
                '非常に分かりやすく解説されており、すぐに実践したくなりました。',
            ],
            4 => [
                '全体を通して非常にタメになりました。おすすめの一冊です。',
                '共感できる部分が多く、最後まで一気に読み終えました。',
                '少し難しい部分もありましたが、知的好奇心が刺激されました。',
            ],
            3 => [
                '標準的な内容でしたが、参考になる部分もいくつかありました。',
                '期待値が高すぎたせいか、やや物足りなさを感じました。',
            ],
        ];

        // 各書籍に2〜4件のレビューを確実に行き渡らせる（計32件目安）
        foreach ($books as $index => $book) {
            // 書籍ごとに2〜4件のレビューをランダムまたは順番に割り振る
            $reviewCount = ($index % 3) + 2; // 2, 3, 4 のいずれか
            $shuffledUsers = $users->shuffle();

            for ($i = 0; $i < min($reviewCount, $users->count()); $i++) {
                $user = $shuffledUsers[$i];
                $rating = rand(3, 5);
                $ratingComments = $comments[$rating];
                $comment = $ratingComments[array_rand($ratingComments)];

                Review::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'rating' => $rating,
                    'comment' => "【{$user->name}さんの感想】" . $comment,
                ]);
            }
        }
    }
}