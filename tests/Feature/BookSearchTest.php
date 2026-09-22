<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookSearchTest extends TestCase
{
    use RefreshDatabase;

    private function createBook(
        User $user,
        string $title,
        string $author,
        string $isbn
    ): Book {
        return Book::create([
            'user_id' => $user->id,
            'title' => $title,
            'author' => $author,
            'isbn' => $isbn,
            'published_date' => '2026-01-01',
            'description' => '検索テスト用の書籍です。',
        ]);
    }

    public function test_books_can_be_searched_by_title_keyword(): void
    {
        $user = User::factory()->create();

        $this->createBook(
            $user,
            'Laravel入門',
            '山田太郎',
            '9781234567890'
        );

        $this->createBook(
            $user,
            'PHP実践',
            '佐藤花子',
            '9781234567891'
        );

        $response = $this->get('/books?keyword=Laravel');

        $response
            ->assertOk()
            ->assertSee('Laravel入門')
            ->assertDontSee('PHP実践');
    }

    public function test_books_can_be_searched_by_author_keyword(): void
    {
        $user = User::factory()->create();

        $this->createBook(
            $user,
            'Laravel入門',
            '山田太郎',
            '9781234567890'
        );

        $this->createBook(
            $user,
            'PHP実践',
            '佐藤花子',
            '9781234567891'
        );

        $response = $this->get('/books?keyword=佐藤');

        $response
            ->assertOk()
            ->assertSee('PHP実践')
            ->assertDontSee('Laravel入門');
    }

    public function test_books_can_be_filtered_by_genre(): void
    {
        $user = User::factory()->create();

        $technical = Genre::create([
            'user_id' => $user->id,
            'name' => '技術書',
        ]);

        $novel = Genre::create([
            'user_id' => $user->id,
            'name' => '小説',
        ]);

        $technicalBook = $this->createBook(
            $user,
            'Laravel入門',
            '山田太郎',
            '9781234567890'
        );

        $novelBook = $this->createBook(
            $user,
            'テスト小説',
            '佐藤花子',
            '9781234567891'
        );

        $technicalBook->genres()->sync([$technical->id]);
        $novelBook->genres()->sync([$novel->id]);

        $response = $this->get(
            '/books?genre=' . $technical->id
        );

        $response
            ->assertOk()
            ->assertSee('Laravel入門')
            ->assertDontSee('テスト小説');
    }

    public function test_books_can_be_sorted_by_oldest(): void
    {
        $user = User::factory()->create();

        $oldBook = $this->createBook(
            $user,
            '古い本',
            '著者A',
            '9781234567890'
        );

        $newBook = $this->createBook(
            $user,
            '新しい本',
            '著者B',
            '9781234567891'
        );

        $oldBook->update([
            'created_at' => now()->subDay(),
        ]);

        $newBook->update([
            'created_at' => now(),
        ]);

        $response = $this->get('/books?sort=oldest');

        $response
            ->assertOk()
            ->assertSeeInOrder([
                '古い本',
                '新しい本',
            ]);
    }

    public function test_books_can_be_sorted_by_title(): void
    {
        $user = User::factory()->create();

        $this->createBook(
            $user,
            'Bの本',
            '著者B',
            '9781234567890'
        );

        $this->createBook(
            $user,
            'Aの本',
            '著者A',
            '9781234567891'
        );

        $response = $this->get('/books?sort=title');

        $response
            ->assertOk()
            ->assertSeeInOrder([
                'Aの本',
                'Bの本',
            ]);
    }

    public function test_books_can_be_sorted_by_rating(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();

        $lowRatedBook = $this->createBook(
            $owner,
            '低評価の本',
            '著者A',
            '9781234567890'
        );

        $highRatedBook = $this->createBook(
            $owner,
            '高評価の本',
            '著者B',
            '9781234567891'
        );

        Review::create([
            'user_id' => $reviewer->id,
            'book_id' => $lowRatedBook->id,
            'rating' => 2,
            'comment' => '普通でした。',
        ]);

        Review::create([
            'user_id' => $reviewer->id,
            'book_id' => $highRatedBook->id,
            'rating' => 5,
            'comment' => 'とても良かったです。',
        ]);

        $response = $this->get('/books?sort=rating');

        $response
            ->assertOk()
            ->assertSeeInOrder([
                '高評価の本',
                '低評価の本',
            ]);
    }

    public function test_book_without_reviews_is_last_when_sorted_by_rating(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();

        $ratedBook = $this->createBook(
            $owner,
            '評価ありの本',
            '著者A',
            '9781234567890'
        );

        $unratedBook = $this->createBook(
            $owner,
            '評価なしの本',
            '著者B',
            '9781234567891'
        );

        Review::create([
            'user_id' => $reviewer->id,
            'book_id' => $ratedBook->id,
            'rating' => 3,
            'comment' => 'レビューあり',
        ]);

        $response = $this->get('/books?sort=rating');

        $response
            ->assertOk()
            ->assertSeeInOrder([
                '評価ありの本',
                '評価なしの本',
            ]);
    }

    public function test_search_conditions_are_kept_in_pagination_links(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 11; $i++) {
            $this->createBook(
                $user,
                'Laravel本' . $i,
                'テスト著者',
                '9781234567' . str_pad(
                    (string) $i,
                    3,
                    '0',
                    STR_PAD_LEFT
                )
            );
        }

        $response = $this->get(
            '/books?keyword=Laravel&sort=title'
        );

        $response
            ->assertOk()
            ->assertSee('keyword=Laravel', false)
            ->assertSee('sort=title', false)
            ->assertSee('page=2', false);
    }
}