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

    public function test_book_isbn_must_be_13_digits(): void
    {
        $user = User::factory()->create();
        $genre = $this->createGenre($user);

        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), [
                'title' => 'ISBNテスト',
                'author' => 'テスト著者',
                'isbn' => '123456789012',
                'published_date' => '2026-01-01',
                'genres' => [$genre->id],
            ]);

        $response->assertSessionHasErrors([
            'isbn' => 'ISBNは13桁で入力してください。',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_book_isbn_must_be_unique(): void
    {
        $user = User::factory()->create();
        $genre = $this->createGenre($user);

        $this->createBook($user);

        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), [
                'title' => 'ISBN重複テスト',
                'author' => 'テスト著者',
                'isbn' => '9781234567890',
                'published_date' => '2026-01-01',
                'genres' => [$genre->id],
            ]);

        $response->assertSessionHasErrors([
            'isbn' => 'そのISBNは既に使用されています。',
        ]);

        $this->assertDatabaseCount('books', 1);
    }

    public function test_book_published_date_is_required(): void
    {
        $user = User::factory()->create();
        $genre = $this->createGenre($user);

        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), [
                'title' => '出版日テスト',
                'author' => 'テスト著者',
                'isbn' => '9781234567894',
                'genres' => [$genre->id],
            ]);

        $response->assertSessionHasErrors([
            'published_date' => '出版日は必須です。',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_book_requires_at_least_one_genre(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), [
                'title' => 'ジャンルテスト',
                'author' => 'テスト著者',
                'isbn' => '9781234567895',
                'published_date' => '2026-01-01',
                'genres' => [],
            ]);

        $response->assertSessionHasErrors([
            'genres' => 'ジャンルは1つ以上選択してください。',
        ]);

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

    public function test_guest_can_view_books_index(): void
    {
        $response = $this->get(route('books.index'));

        $response->assertOk();
    }

    public function test_books_index_displays_ten_books_per_page(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 11; $i++) {
            Book::create([
                'user_id' => $user->id,
                'title' => '一覧テスト書籍' . $i,
                'author' => 'テスト著者',
                'isbn' => '9781234567' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'published_date' => '2026-01-01',
                'description' => '一覧ページネーションテストです。',
            ]);
        }

        $response = $this->get(route('books.index'));

        $response->assertOk();

        $response->assertViewHas('books', function ($books) {
            return $books->count() === 10
                && $books->total() === 11
                && $books->perPage() === 10;
        });
    }

    public function test_books_index_is_ordered_by_newest_first(): void
    {
        $user = User::factory()->create();

        $olderBook = Book::create([
            'user_id' => $user->id,
            'title' => '古い書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567801',
            'published_date' => '2026-01-01',
            'description' => '古い書籍です。',
        ]);

        $olderBook->created_at = '2026-01-01 10:00:00';
        $olderBook->saveQuietly();

        $newerBook = Book::create([
            'user_id' => $user->id,
            'title' => '新しい書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567802',
            'published_date' => '2026-01-02',
            'description' => '新しい書籍です。',
        ]);

        $newerBook->created_at = '2026-01-02 10:00:00';
        $newerBook->saveQuietly();

        $response = $this->get(route('books.index'));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                '新しい書籍',
                '古い書籍',
            ]);
    }
}