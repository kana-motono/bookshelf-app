<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IsbnSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_search_book_by_isbn(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'テスト書籍',
                            'authors' => ['山田太郎', '鈴木花子'],
                            'publishedDate' => '2026-01-15',
                            'description' => 'テスト用の説明です。',
                            'imageLinks' => [
                                'thumbnail' => 'https://example.com/book.jpg',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson('/books/isbn/9784101010014');

        $response
            ->assertOk()
            ->assertJson([
                'title' => 'テスト書籍',
                'author' => '山田太郎, 鈴木花子',
                'published_date' => '2026-01-15',
                'description' => 'テスト用の説明です。',
                'image_url' => 'https://example.com/book.jpg',
            ]);

        Http::assertSent(function ($request) {
            return str_starts_with(
                $request->url(),
                'https://www.googleapis.com/books/v1/volumes'
            )
                && $request['q'] === 'isbn:9784101010014';
        });
    }

    public function test_isbn_must_be_13_digits(): void
    {
        Http::fake();

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson('/books/isbn/12345');

        $response
            ->assertStatus(422)
            ->assertJson([
                'error' => 'ISBNは13桁の数字で入力してください。',
            ]);

        Http::assertNothingSent();
    }

    public function test_book_not_found_returns_404(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 0,
                'items' => [],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson('/books/isbn/9784101010014');

        $response
            ->assertNotFound()
            ->assertJson([
                'error' => '該当する書籍が見つかりませんでした。',
            ]);
    }

    public function test_google_books_api_error_returns_502(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' =>
                Http::response([], 500),
        ]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson('/books/isbn/9784101010014');

        $response
            ->assertStatus(502)
            ->assertJson([
                'error' => '書籍情報を取得できませんでした。',
            ]);
    }

    public function test_guest_cannot_search_book_by_isbn(): void
    {
        Http::fake();

        $response = $this
            ->getJson('/books/isbn/9784101010014');

        $response->assertUnauthorized();

        Http::assertNothingSent();
    }
}