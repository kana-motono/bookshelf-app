<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BookIndexRequest;
use App\Http\Requests\Api\BookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    public function index(BookIndexRequest $request): AnonymousResourceCollection
    {
        $query = Book::query()
            ->with('genres')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating');

        if ($request->filled('keyword')) {
            $keyword = $request->string('keyword')->toString();

            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', '%'.$keyword.'%')
                    ->orWhere('author', 'like', '%'.$keyword.'%')
                    ->orWhere('isbn', 'like', '%'.$keyword.'%');
            });
        }

        if ($request->filled('genre_id')) {
            $genreId = $request->integer('genre_id');

            $query->whereHas('genres', function ($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });
        }

        $perPage = $request->integer('per_page', 20);

        $books = $query
            ->orderByDesc('id')
            ->paginate($perPage);

        return BookResource::collection($books);
    }

    public function show(Book $book): BookResource
    {
        $book->load([
            'genres',
            'reviews.user',
        ])
            ->loadCount('reviews')
            ->loadAvg('reviews', 'rating');

        return new BookResource($book);
    }

    public function store(BookRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $genres = $validated['genres'];
        unset($validated['genres']);

        $book = DB::transaction(function () use ($validated, $genres, $request): Book {
            $book = Book::create(array_merge(
                $validated,
                [
                    'user_id' => $request->user()->id,
                ]
            ));

            $book->genres()->sync($genres);

            return $book;
        });

        $book->load('genres')
            ->loadCount('reviews')
            ->loadAvg('reviews', 'rating');

        return (new BookResource($book))
            ->response()
            ->setStatusCode(201);
    }

    public function update(BookRequest $request, Book $book): BookResource
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        $genres = $validated['genres'];
        unset($validated['genres']);

        DB::transaction(function () use ($book, $validated, $genres): void {
            $book->update($validated);
            $book->genres()->sync($genres);
        });
        $book->load('genres')
            ->loadCount('reviews')
            ->loadAvg('reviews', 'rating');

        return new BookResource($book);
    }

    public function destroy(Book $book): Response
    {
        $this->authorize('delete', $book);

        $book->delete();

        return response()->noContent();
    }
}
