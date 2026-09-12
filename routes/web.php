<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\GenreController;

Route::get('/', function () {
    return view('welcome');
});

// 会員登録
Route::get('/register', function () {
    return view('auth.register');
})->middleware('guest')->name('register');

Route::post('/register', [AuthController::class, 'register']);

// ログイン
Route::get('/login', function () {
    return view('auth.login');
})->middleware('guest')->name('login');

Route::post('/login', [AuthController::class, 'login']);

// 認証必須
Route::middleware(['auth'])->group(function () {

    // 書籍一覧
    Route::get('/books', [BookController::class, 'index'])
        ->name('books.index');

    // 書籍作成
    Route::get('/books/create', [BookController::class, 'create'])
        ->name('books.create');

    Route::post('/books', [BookController::class, 'store'])
        ->name('books.store');

    // 書籍編集
    Route::get('/books/{book}/edit', [BookController::class, 'edit'])
        ->name('books.edit');

    Route::put('/books/{book}', [BookController::class, 'update'])
        ->name('books.update');

    // 書籍削除
    Route::delete('/books/{book}', [BookController::class, 'destroy'])
        ->name('books.destroy');

    // レビュー投稿
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])
        ->name('reviews.store');

    // レビュー編集
    Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])
        ->name('reviews.edit');

    // レビュー更新
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])
        ->name('reviews.update');

    // レビュー削除
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])
        ->name('reviews.destroy');

    // レビューいいね・解除
    Route::post('/reviews/{review}/like', [ReviewController::class, 'toggleLike'])
        ->name('reviews.like');

    // 書籍詳細
    Route::get('/books/{book}', [BookController::class, 'show'])
        ->name('books.show');

    // お気に入り一覧
    Route::get('/favorites', [FavoriteController::class, 'index'])
        ->name('favorites.index');

    // お気に入り登録・解除
    Route::post('/books/{book}/favorite', [FavoriteController::class, 'toggle'])
        ->name('favorites.toggle');
});

// ランキング
Route::get('/ranking', [RankingController::class, 'index'])
    ->name('ranking.index');

// ジャンル一覧
Route::middleware(['auth'])->group(function () {
    Route::get('/genres', [GenreController::class, 'index'])
        ->name('genres.index');

    Route::get('/genres/create', [GenreController::class, 'create'])
        ->name('genres.create');

    Route::post('/genres', [GenreController::class, 'store'])
        ->name('genres.store');

    Route::get('/genres/{genre}', [GenreController::class, 'show'])
        ->name('genres.show');

    Route::get('/genres/{genre}/edit', [GenreController::class, 'edit'])
        ->name('genres.edit');

    Route::put('/genres/{genre}', [GenreController::class, 'update'])
        ->name('genres.update');

    Route::delete('/genres/{genre}', [GenreController::class, 'destroy'])
        ->name('genres.destroy');
});