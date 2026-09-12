<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        $books = [
            [
                'title' => '吾輩は猫である',
                'author' => '夏目漱石',
                'isbn' => '9784101010014',
                'published_date' => '1905-01-01',
                'genres' => ['小説'],
                'description' => '吾輩は猫である。名前はまだ無い。迷子の猫の視点から描かれた明治の人間模様。',
            ],
            [
                'title' => '人を動かす',
                'author' => 'D・カーネギー',
                'isbn' => '9784422100524',
                'published_date' => '1936-10-01',
                'genres' => ['ビジネス', '自己啓発'],
                'description' => '人間関係の原則を説き、世界中で読み継がれている不朽の自己啓発名著。',
            ],
            [
                'title' => 'リーダブルコード',
                'author' => 'Dustin Boswell',
                'isbn' => '9784873115658',
                'published_date' => '2012-06-23',
                'genres' => ['技術書'],
                'description' => 'より良いコードを書くためのシンプルで実践的なテクニックを解説。',
            ],
            [
                'title' => '7つの習慣',
                'author' => 'スティーブン・R・コヴィー',
                'isbn' => '9784863940246',
                'published_date' => '2013-08-30',
                'genres' => ['ビジネス', '自己啓発'],
                'description' => '人格主義を掲げ、真の成功と効果性を高めるための7つの原則。',
            ],
            [
                'title' => '坊っちゃん',
                'author' => '夏目漱石',
                'isbn' => '9784101010021',
                'published_date' => '1906-04-01',
                'genres' => ['小説'],
                'description' => '親譲りの無鉄砲で子供の時から損ばかりしている主人公の痛快な物語。',
            ],
            [
                'title' => 'サピエンス全史',
                'author' => 'ユヴァル・ノア・ハラリ',
                'isbn' => '9784309226712',
                'published_date' => '2016-09-08',
                'genres' => ['歴史', '科学'],
                'description' => '虚構を信じる能力を武器に地球の覇者となった人類の壮大な歴史。',
            ],
            [
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'isbn' => '9784048930598',
                'published_date' => '2017-12-18',
                'genres' => ['技術書'],
                'description' => 'アジャイルソフトウェア熟練者が明かす保守性の高いコードの書き方。',
            ],
            [
                'title' => '嫌われる勇気',
                'author' => '岸見一郎・古賀史健',
                'isbn' => '9784478025819',
                'published_date' => '2013-12-13',
                'genres' => ['自己啓発'],
                'description' => 'アドラー心理学を対話形式で分かりやすく紐解き、人生の悩みをクリアにする。',
            ],
            [
                'title' => '火花',
                'author' => '又吉直樹',
                'isbn' => '9784163902302',
                'published_date' => '2015-03-11',
                'genres' => ['小説'],
                'description' => '売れない芸人が奇抜な先輩芸人との交流を通じて見つめる人間模様。',
            ],
            [
                'title' => 'FACTFULNESS',
                'author' => 'ハンス・ロスリング',
                'isbn' => '9784822289607',
                'published_date' => '2019-01-11',
                'genres' => ['ビジネス', '科学'],
                'description' => 'データと事実に基づき、世界を正しく見るための習慣を身につける。',
            ],
            [
                'title' => 'コンテナ物語',
                'author' => 'マルク・レビンソン',
                'isbn' => '9784822251468',
                'published_date' => '2007-01-18',
                'genres' => ['ビジネス', '歴史'],
                'description' => '世界経済を大きく変えたコンテナ輸送の誕生と発展の裏側を描くノンフィクション。',
            ],
        ];

        foreach ($books as $index => $bookData) {
            $genreNames = $bookData['genres'];

            unset($bookData['genres']);

            $number = $index + 1;

            $bookData['user_id'] = $user->id;
            $bookData['image_url']
                = "https://placehold.co/200x300/e2e8f0/475569?text={$number}";

            $book = Book::firstOrCreate(
                ['isbn' => $bookData['isbn']],
                $bookData
            );

            $genreIds = Genre::whereIn('name', $genreNames)
                ->pluck('id');

            $book->genres()->sync($genreIds);
        }
    }
}