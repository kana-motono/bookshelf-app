<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function createBook(
        User $user,
        string $title = 'テスト書籍'
    ): Book {
        return Book::create([
            'user_id' => $user->id,
            'title' => $title,
            'author' => 'テスト著者',
        ]);
    }

    private function createReview(
        User $user,
        Book $book,
        int $rating
    ): Review {
        return Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => $rating,
            'comment' => 'テストレビューです。',
        ]);
    }

    public function test_guest_cannot_view_reports(): void
    {
        $response = $this->get('/reports');

        $response->assertRedirect('/login');
    }

    public function test_user_can_view_reports_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/reports');

        $response->assertOk();
    }

    public function test_report_counts_only_logged_in_users_reviews(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $book1 = $this->createBook($user, '書籍A');
        $book2 = $this->createBook($user, '書籍B');
        $otherBook = $this->createBook($otherUser, '他人の書籍');

        $this->createReview($user, $book1, 5);
        $this->createReview($user, $book2, 3);

        $this->createReview(
            $otherUser,
            $otherBook,
            1
        );

        $response = $this->actingAs($user)
            ->get('/reports');

        $response->assertOk();

        $response->assertViewHas(
            'stats',
            function (array $stats): bool {
                return $stats['summary']['total_reviews'] === 2
                    && $stats['summary']['average_rating'] === 4.0;
            }
        );
    }

    public function test_report_counts_completed_reading_plans_as_books_read(): void
    {
        $user = User::factory()->create();

        $completedBook = $this->createBook(
            $user,
            '読了した本'
        );

        $plannedBook = $this->createBook(
            $user,
            'まだ読んでいる本'
        );

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $completedBook->id,
            'target_date' => now()->subDay()->toDateString(),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $plannedBook->id,
            'target_date' => now()->addDays(7)->toDateString(),
            'status' => ReadingPlanStatus::Planned,
        ]);

        $response = $this->actingAs($user)
            ->get('/reports');

        $response->assertViewHas(
            'stats',
            function (array $stats): bool {
                return $stats['summary']['books_read'] === 1;
            }
        );
    }

    public function test_report_creates_rating_distribution(): void
    {
        $user = User::factory()->create();

        $book1 = $this->createBook($user, '5点の本');
        $book2 = $this->createBook($user, 'もう1冊の5点の本');
        $book3 = $this->createBook($user, '3点の本');

        $this->createReview($user, $book1, 5);
        $this->createReview($user, $book2, 5);
        $this->createReview($user, $book3, 3);

        $response = $this->actingAs($user)
            ->get('/reports');

        $response->assertViewHas(
            'stats',
            function (array $stats): bool {
                $distribution = $stats['rating_distribution'];

                return $distribution[0] === 0
                    && $distribution[1] === 0
                    && $distribution[2] === 1
                    && $distribution[3] === 0
                    && $distribution[4] === 2;
            }
        );
    }

    public function test_top_rated_books_contains_only_rating_four_or_higher(): void
    {
        $user = User::factory()->create();

        $highRatedBook = $this->createBook(
            $user,
            '高評価の本'
        );

        $lowRatedBook = $this->createBook(
            $user,
            '低評価の本'
        );

        $this->createReview(
            $user,
            $highRatedBook,
            5
        );

        $this->createReview(
            $user,
            $lowRatedBook,
            3
        );

        $response = $this->actingAs($user)
            ->get('/reports');

        $response->assertViewHas(
            'stats',
            function (array $stats): bool {
                $titles = $stats['top_rated_books']
                    ->pluck('title');

                return $titles->contains('高評価の本')
                    && !$titles->contains('低評価の本');
            }
        );
    }

    public function test_top_rated_books_is_limited_to_five_books(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 6; $i++) {
            $book = $this->createBook(
                $user,
                "高評価書籍{$i}"
            );

            $this->createReview(
                $user,
                $book,
                5
            );
        }

        $response = $this->actingAs($user)
            ->get('/reports');

        $response->assertViewHas(
            'stats',
            function (array $stats): bool {
                return $stats['top_rated_books']->count() === 5;
            }
        );
    }

    public function test_report_calculates_genre_rating_from_users_reviews(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $genre = Genre::create([
            'user_id' => $user->id,
            'name' => 'ミステリー',
        ]);

        $book1 = $this->createBook($user, '本A');
        $book2 = $this->createBook($user, '本B');

        $book1->genres()->attach($genre->id);
        $book2->genres()->attach($genre->id);

        $this->createReview($user, $book1, 5);
        $this->createReview($user, $book2, 3);

        // 他人のレビューはジャンル評価に含めない
        $this->createReview(
            $otherUser,
            $book1,
            1
        );

        $response = $this->actingAs($user)
            ->get('/reports');

        $response->assertViewHas(
            'stats',
            function (array $stats): bool {
                $genreStats = $stats['genre_ratings']->first();

                return $genreStats !== null
                    && $genreStats['name'] === 'ミステリー'
                    && $genreStats['count'] === 2
                    && (float) $genreStats['average_rating'] === 4.0;
            }
        );
    }
}