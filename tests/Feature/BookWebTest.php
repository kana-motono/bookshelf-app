<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookWebTest extends TestCase
{
    use RefreshDatabase;

    private function createBook(User $user): Book
    {
        return Book::create([
            'user_id' => $user->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => '書籍CRUDテスト用です。',
        ]);
    }

    private function createGenre(User $user): Genre
    {
        return Genre::create([
            'user_id' => $user->id,
            'name' => 'テストジャンル',
        ]);
    }

    public function test_authenticated_user_can_view_books_index(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('books.index'));

        $response->assertOk();
    }

    public function test_authenticated_user_can_create_book(): void
    {
        $user = User::factory()->create();
        $genre = $this->createGenre($user);

        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), [
                'title' => '新規書籍',
                'author' => '新規著者',
                'isbn' => '9781234567891',
                'published_date' => '2026-02-01',
                'description' => '新規登録テストです。',
                'genres' => [$genre->id],
            ]);

        $book = Book::where('title', '新規書籍')->first();

        $this->assertNotNull($book);

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => '新規書籍',
            'isbn' => '9781234567891',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_book_title_is_required(): void
    {
        $user = User::factory()->create();
        $genre = $this->createGenre($user);

        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), [
                'author' => '著者',
                'isbn' => '9781234567892',
                'genres' => [$genre->id],
            ]);

        $response->assertSessionHasErrors('title');

        $this->assertDatabaseCount('books', 0);
    }

    public function test_owner_can_update_book(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);
        $genre = $this->createGenre($user);

        $response = $this
            ->actingAs($user)
            ->put(route('books.update', $book), [
                'title' => '更新後タイトル',
                'author' => '更新後著者',
                'isbn' => $book->isbn,
                'published_date' => '2026-03-01',
                'description' => '更新しました。',
                'genres' => [$genre->id],
            ]);

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_other_user_cannot_update_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = $this->createBook($owner);
        $genre = $this->createGenre($owner);

        $response = $this
            ->actingAs($otherUser)
            ->put(route('books.update', $book), [
                'title' => '不正更新',
                'author' => '不正著者',
                'isbn' => $book->isbn,
                'published_date' => '2026-03-01',
                'description' => '不正更新',
                'genres' => [$genre->id],
            ]);

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

        $response = $this
            ->actingAs($user)
            ->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_other_user_cannot_delete_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = $this->createBook($owner);

        $response = $this
            ->actingAs($otherUser)
            ->delete(route('books.destroy', $book));

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_guest_cannot_create_book(): void
    {
        $response = $this->post(route('books.store'), [
            'title' => 'ゲスト書籍',
            'author' => 'ゲスト著者',
            'isbn' => '9781234567893',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseCount('books', 0);
    }

    public function test_guest_can_view_book_detail(): void
    {
        $user = User::factory()->create();

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'ゲスト閲覧テスト',
            'author' => 'テスト著者',
            'isbn' => '9781234567897',
            'published_date' => '2026-01-01',
            'description' => 'ゲスト閲覧確認用です。',
        ]);

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee('ゲスト閲覧テスト');
    }
}
