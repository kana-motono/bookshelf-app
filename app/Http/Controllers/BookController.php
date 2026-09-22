<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        $query = $this->buildBookQuery($request);

        $books = $query
            ->paginate(10)
            ->withQueryString();

        $genres = Genre::orderBy('name')->get();

        $sort = $request->input('sort', 'latest');

        return view('books.index', compact(
            'books',
            'genres',
            'sort'
        ));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $books = $this->buildBookQuery($request)->get();

        $fileName = 'books_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(
            function () use ($books) {
                $handle = fopen('php://output', 'w');

                // Excelで日本語が文字化けしにくいようにBOMを付ける
                fwrite($handle, "\xEF\xBB\xBF");

                // CSVの見出し
                fputcsv($handle, [
                    'ID',
                    'タイトル',
                    '著者',
                    'ISBN',
                    '出版日',
                    'ジャンル',
                    '平均評価',
                ]);

                foreach ($books as $book) {
                    fputcsv($handle, [
                        $book->id,
                        $book->title,
                        $book->author,
                        $book->isbn,
                        $book->published_date,
                        $book->genres->pluck('name')->implode(' / '),
                        $book->reviews_avg_rating !== null
                        ? number_format($book->reviews_avg_rating, 1)
                        : '',
                    ]);
                }

                fclose($handle);
            },
            $fileName,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

    public function create(): View
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    public function store(BookRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $genres = $validated['genres'];
        unset($validated['genres']);

        $book = Book::create(array_merge(
            $validated,
            [
                'user_id' => $request->user()->id,
            ]
        ));

        $book->genres()->sync($genres);

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を登録しました。');
    }

    public function show(Book $book): View
    {
        $book->load('genres', 'reviews.user');

        return view('books.show', compact('book'));
    }

    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $genres = Genre::all();

        return view('books.edit', compact('book', 'genres'));
    }

    public function update(
        BookRequest $request,
        Book $book
    ): RedirectResponse {
        $this->authorize('update', $book);

        $validated = $request->validated();

        $genres = $validated['genres'];
        unset($validated['genres']);

        $book->update($validated);
        $book->genres()->sync($genres);

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を更新しました。');
    }

    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を削除しました。');
    }

    private function buildBookQuery(Request $request): Builder
    {
        $query = Book::with('genres')
            ->withAvg('reviews', 'rating');

        // キーワード検索
        if ($request->filled('keyword')) {
            $keyword = $request->string('keyword')->toString();

            $query->where(function ($q) use ($keyword) {
                $q->where(
                    'title',
                    'like',
                    '%' . $keyword . '%'
                )->orWhere(
                        'author',
                        'like',
                        '%' . $keyword . '%'
                    );
            });
        }

        // ジャンル絞り込み
        if ($request->filled('genre')) {
            $genreId = $request->integer('genre');

            $query->whereHas(
                'genres',
                function ($q) use ($genreId) {
                    $q->where('genres.id', $genreId);
                }
            );
        }

        // 並び替え
        $sort = $request->input('sort', 'latest');

        switch ($sort) {
            case 'oldest':
                $query->oldest();
                break;

            case 'title':
                $query->orderBy('title');
                break;

            case 'rating':
                $query
                    ->orderByRaw('reviews_avg_rating IS NULL')
                    ->orderByDesc('reviews_avg_rating');
                break;

            case 'latest':
            default:
                $query->latest();
                break;
        }

        return $query;
    }
}