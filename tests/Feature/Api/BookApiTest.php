<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    private function createGenre(User $user, string $name = 'テストジャンル'): Genre
    {
        return Genre::create([
            'user_id' => $user->id,
            'name' => $name,
        ]);
    }

    private function createBook(
        User $user,
        string $title = 'テスト書籍',
        string $isbn = '9781234567890'
    ): Book {
        return Book::create([
            'user_id' => $user->id,
            'title' => $title,
            'author' => 'テスト著者',
            'isbn' => $isbn,
            'published_date' => '2026-01-01',
            'description' => 'APIテスト用の書籍です。',
        ]);
    }

    public function test_book_list_can_be_accessed_without_authentication(): void
    {
        $user = User::factory()->create();

        $book = $this->createBook($user);

        $response = $this->getJson('/api/v1/books');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $book->id)
            ->assertJsonPath('data.0.title', 'テスト書籍');
    }

    public function test_book_list_can_search_by_keyword(): void
    {
        $user = User::factory()->create();

        $this->createBook(
            $user,
            'Laravel入門',
            '9781234567890'
        );

        $this->createBook(
            $user,
            'PHP実践',
            '9781234567891'
        );

        $response = $this->getJson('/api/v1/books?keyword=Laravel');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel入門');
    }

    public function test_book_list_can_filter_by_genre(): void
    {
        $user = User::factory()->create();

        $genre1 = $this->createGenre($user, '技術書');
        $genre2 = $this->createGenre($user, '小説');

        $book1 = $this->createBook(
            $user,
            '技術書籍',
            '9781234567890'
        );

        $book2 = $this->createBook(
            $user,
            '小説書籍',
            '9781234567891'
        );

        $book1->genres()->sync([$genre1->id]);
        $book2->genres()->sync([$genre2->id]);

        $response = $this->getJson(
            '/api/v1/books?genre_id=' . $genre1->id
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', '技術書籍');
    }

    public function test_book_detail_can_be_accessed_without_authentication(): void
    {
        $user = User::factory()->create();

        $genre = $this->createGenre($user);
        $book = $this->createBook($user);

        $book->genres()->sync([$genre->id]);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '最高です。',
        ]);

        $response = $this->getJson(
            '/api/v1/books/' . $book->id
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.title', 'テスト書籍')
            ->assertJsonPath('data.review_count', 1)
            ->assertJsonPath('data.average_rating', 5);
    }

    public function test_nonexistent_book_returns_404(): void
    {
        $response = $this->getJson('/api/v1/books/999999');

        $response->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_create_book(): void
    {
        $response = $this->postJson('/api/v1/books', [
            'title' => '未認証書籍',
            'author' => '未認証著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'genres' => [1],
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_book(): void
    {
        $user = User::factory()->create();
        $genre = $this->createGenre($user);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', [
            'title' => 'API新規書籍',
            'author' => 'API著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'API作成テスト',
            'genres' => [$genre->id],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'API新規書籍')
            ->assertJsonPath('data.author', 'API著者');

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'API新規書籍',
            'isbn' => '9781234567890',
        ]);
    }

    public function test_create_validation_messages_are_returned_in_japanese(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'author',
                'isbn',
                'published_date',
                'genres',
            ])
            ->assertJsonPath(
                'errors.title.0',
                'タイトルは必須です。'
            );
    }

    public function test_owner_can_update_book(): void
    {
        $user = User::factory()->create();
        $genre = $this->createGenre($user);
        $book = $this->createBook($user);

        Sanctum::actingAs($user);

        $response = $this->putJson(
            '/api/v1/books/' . $book->id,
            [
                'title' => 'API更新後タイトル',
                'author' => '更新後著者',
                'isbn' => $book->isbn,
                'published_date' => '2026-02-01',
                'description' => '更新後説明',
                'genres' => [$genre->id],
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.title',
                'API更新後タイトル'
            );

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'API更新後タイトル',
        ]);
    }

    public function test_other_user_cannot_update_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $genre = $this->createGenre($owner);
        $book = $this->createBook($owner);

        Sanctum::actingAs($otherUser);

        $response = $this->putJson(
            '/api/v1/books/' . $book->id,
            [
                'title' => '不正更新',
                'author' => '不正著者',
                'isbn' => $book->isbn,
                'published_date' => '2026-02-01',
                'genres' => [$genre->id],
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'テスト書籍',
        ]);
    }

    public function test_owner_can_delete_book(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        Sanctum::actingAs($user);

        $response = $this->deleteJson(
            '/api/v1/books/' . $book->id
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_other_user_cannot_delete_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = $this->createBook($owner);

        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson(
            '/api/v1/books/' . $book->id
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }
}