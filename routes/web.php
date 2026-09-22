<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/register', function () {
    return view('auth.register');
})->middleware('guest')->name('register');

Route::post('/register', [AuthController::class, 'register']);

Route::get('/login', function () {
    return view('auth.login');
})->middleware('guest')->name('login');

Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| 書籍公開ページ
|--------------------------------------------------------------------------
*/

Route::get('/books', [BookController::class, 'index'])
    ->name('books.index');

Route::get('/books/export/csv', [BookController::class, 'exportCsv'])
    ->name('books.export.csv');

/*
|--------------------------------------------------------------------------
| 認証必須ページ
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | 書籍
    |--------------------------------------------------------------------------
    */

    Route::get('/books/create', [BookController::class, 'create'])
        ->name('books.create');

    Route::post('/books', [BookController::class, 'store'])
        ->name('books.store');

    Route::get('/books/{book}/edit', [BookController::class, 'edit'])
        ->name('books.edit');

    Route::put('/books/{book}', [BookController::class, 'update'])
        ->name('books.update');

    Route::delete('/books/{book}', [BookController::class, 'destroy'])
        ->name('books.destroy');

    /*
    |--------------------------------------------------------------------------
    | レビュー
    |--------------------------------------------------------------------------
    */

    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])
        ->name('reviews.store');

    Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])
        ->name('reviews.edit');

    Route::put('/reviews/{review}', [ReviewController::class, 'update'])
        ->name('reviews.update');

    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])
        ->name('reviews.destroy');

    Route::post('/reviews/{review}/like', [ReviewController::class, 'toggleLike'])
        ->name('reviews.like');

    /*
    |--------------------------------------------------------------------------
    | お気に入り
    |--------------------------------------------------------------------------
    */

    Route::get('/favorites', [FavoriteController::class, 'index'])
        ->name('favorites.index');

    Route::post('/books/{book}/favorites', [FavoriteController::class, 'toggle'])
        ->name('favorites.toggle');

    /*
    |--------------------------------------------------------------------------
    | 読書計画
    |--------------------------------------------------------------------------
    */

    Route::get('/reading-plans', [ReadingPlanController::class, 'index'])
        ->name('reading-plans.index');

    Route::get('/reading-plans/create', [ReadingPlanController::class, 'create'])
        ->name('reading-plans.create');

    Route::post('/reading-plans', [ReadingPlanController::class, 'store'])
        ->name('reading-plans.store');

    Route::get('/reading-plans/{readingPlan}/edit', [ReadingPlanController::class, 'edit'])
        ->name('reading-plans.edit');

    Route::put('/reading-plans/{readingPlan}', [ReadingPlanController::class, 'update'])
        ->name('reading-plans.update');

    Route::post('/reading-plans/{readingPlan}/complete', [ReadingPlanController::class, 'complete'])
        ->name('reading-plans.complete');

    Route::delete('/reading-plans/{readingPlan}', [ReadingPlanController::class, 'destroy'])
        ->name('reading-plans.destroy');

    /*
    |--------------------------------------------------------------------------
    | 通知
    |--------------------------------------------------------------------------
    */

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');

    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])
        ->name('notifications.read');
});

/*
|--------------------------------------------------------------------------
| 書籍詳細
|--------------------------------------------------------------------------
|
| /books/create より後ろに置くことで、
| create が {book} として解釈されるのを防ぎます。
|
*/

Route::get('/books/{book}', [BookController::class, 'show'])
    ->name('books.show');

/*
|--------------------------------------------------------------------------
| ランキング
|--------------------------------------------------------------------------
*/

Route::get('/ranking', [RankingController::class, 'index'])
    ->name('ranking.index');

/*
|--------------------------------------------------------------------------
| ジャンル
|--------------------------------------------------------------------------
*/

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

    /*
    |--------------------------------------------------------------------------
    | マイレポート
    |--------------------------------------------------------------------------
    */

    Route::get('/reports', [ReportController::class, 'index'])
        ->name('reports.index');        
        
});