<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | 自分が投稿したレビュー
        |--------------------------------------------------------------------------
        */
        $userReviews = Review::query()
            ->where('user_id', $user->id);

        $totalReviews = (clone $userReviews)->count();

        $averageRating = (float) (
            (clone $userReviews)->avg('rating') ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | 読了した本の数
        |--------------------------------------------------------------------------
        */
        $booksRead = $user->readingPlans()
            ->where(
                'status',
                ReadingPlanStatus::Completed->value
            )
            ->distinct()
            ->count('book_id');

        /*
        |--------------------------------------------------------------------------
        | 星1〜5の評価分布
        |--------------------------------------------------------------------------
        */
        $ratingCounts = (clone $userReviews)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating');

        $ratingDistribution = collect(range(1, 5))
            ->map(function (int $rating) use ($ratingCounts): int {
                return (int) ($ratingCounts[$rating] ?? 0);
            });

        /*
        |--------------------------------------------------------------------------
        | 高評価書籍 TOP5
        |--------------------------------------------------------------------------
        */
        $topRatedBooks = Review::query()
            ->with('book')
            ->where('user_id', $user->id)
            ->where('rating', '>=', 4)
            ->orderByDesc('rating')
            ->limit(5)
            ->get()
            ->map(function (Review $review): array {
                return [
                    'id' => $review->book->id,
                    'title' => $review->book->title,
                    'author' => $review->book->author,
                    'rating' => (int) $review->rating,
                ];
            });

        /*
        |--------------------------------------------------------------------------
        | ジャンル別評価
        |--------------------------------------------------------------------------
        |
        | ログインユーザー自身のレビューだけ取得して、
        | レビュー対象の本についているジャンルごとに評価を集計する。
        |
        */
        $reviewsForGenres = Review::query()
            ->with('book.genres')
            ->where('user_id', $user->id)
            ->get();

        $genreRatings = $reviewsForGenres
            ->flatMap(function (Review $review) {
                return $review->book->genres->map(
                    function ($genre) use ($review): array {
                        return [
                            'genre' => $genre,
                            'rating' => (int) $review->rating,
                        ];
                    }
                );
            })
            ->groupBy(function (array $item) {
                return $item['genre']->id;
            })
            ->map(function ($items): array {
                $genre = $items->first()['genre'];

                return [
                    'id' => $genre->id,
                    'name' => $genre->name,
                    'count' => $items->count(),
                    'average_rating' => $items->avg('rating'),
                ];
            })
            ->sortByDesc('average_rating')
            ->take(5)
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Bladeへ渡すデータ
        |--------------------------------------------------------------------------
        */
        $stats = [
            'summary' => [
                'total_reviews' => $totalReviews,
                'books_read' => $booksRead,
                'average_rating' => $averageRating,
            ],

            'rating_distribution' => $ratingDistribution,

            'top_rated_books' => $topRatedBooks,

            'genre_ratings' => $genreRatings,
        ];

        return view(
            'reports.index',
            compact('stats')
        );
    }
}