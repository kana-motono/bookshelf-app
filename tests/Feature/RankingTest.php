<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    private function createBook(User $user, string $title, string $isbn): Book
    {
        return Book::create([
            'user_id' => $user->id,
            'title' => $title,
            'author' => 'テスト著者',
            'isbn' => $isbn,
            'published_date' => '2026-01-01',
            'description' => 'ランキングテスト用の書籍です。',
        ]);
    }

    public function test_ranking_page_is_accessible(): void
    {
        $response = $this->get(route('ranking.index'));

        $response->assertOk();
    }

    public function test_books_are_ordered_by_average_rating_descending(): void
    {
        $user = User::factory()->create();

        $highRatedBook = $this->createBook(
            $user,
            '高評価の本',
            '9781234567890'
        );

        $lowRatedBook = $this->createBook(
            $user,
            '低評価の本',
            '9781234567891'
        );

        Review::create([
            'user_id' => $user->id,
            'book_id' => $highRatedBook->id,
            'rating' => 5,
            'comment' => '高評価',
        ]);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $lowRatedBook->id,
            'rating' => 3,
            'comment' => '低評価',
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();

        $response->assertSeeInOrder([
            '高評価の本',
            '低評価の本',
        ]);
    }

    public function test_review_count_is_used_when_average_rating_is_same(): void
    {
        $user = User::factory()->create();

        $manyReviewsBook = $this->createBook(
            $user,
            'レビュー数が多い本',
            '9781234567892'
        );

        $fewReviewsBook = $this->createBook(
            $user,
            'レビュー数が少ない本',
            '9781234567893'
        );

        Review::create([
            'user_id' => $user->id,
            'book_id' => $manyReviewsBook->id,
            'rating' => 5,
            'comment' => 'レビュー1',
        ]);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $manyReviewsBook->id,
            'rating' => 5,
            'comment' => 'レビュー2',
        ]);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $fewReviewsBook->id,
            'rating' => 5,
            'comment' => 'レビュー1',
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();

        $response->assertSeeInOrder([
            'レビュー数が多い本',
            'レビュー数が少ない本',
        ]);
    }

    public function test_books_without_reviews_are_not_in_ranking(): void
    {
        $user = User::factory()->create();

        $reviewedBook = $this->createBook(
            $user,
            'レビューあり',
            '9781234567894'
        );

        $unreviewedBook = $this->createBook(
            $user,
            'レビューなし',
            '9781234567895'
        );

        Review::create([
            'user_id' => $user->id,
            'book_id' => $reviewedBook->id,
            'rating' => 4,
            'comment' => 'レビューあり',
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();

        $response->assertSee('レビューあり');
        $response->assertDontSee('レビューなし');
    }

    public function test_ranking_contains_at_most_ten_books(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 11; $i++) {
            $book = $this->createBook(
                $user,
                'ランキング本' . $i,
                '9781234567' . str_pad((string) $i, 3, '0', STR_PAD_LEFT)
            );

            Review::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => 5,
                'comment' => 'ランキング用レビュー',
            ]);
        }

        $response = $this->get(route('ranking.index'));

        $response->assertOk();

        $books = $response->viewData('rankedBooks');

        $this->assertCount(10, $books);
    }
}