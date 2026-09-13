<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_genre(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('genres.store'), [
                'name' => 'テストジャンル',
            ]);

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'name' => 'テストジャンル',
            'user_id' => $user->id,
        ]);
    }

    public function test_genre_detail_displays_ten_books_per_page(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'user_id' => $user->id,
            'name' => 'ページネーション確認',
        ]);

        for ($i = 1; $i <= 11; $i++) {
            $book = Book::create([
                'user_id' => $user->id,
                'title' => 'テスト書籍' . $i,
                'author' => 'テスト著者',
                'isbn' => '97812345678' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'published_date' => '2026-01-01',
                'description' => 'テスト用書籍です。',
            ]);

            $book->genres()->attach($genre->id);
        }

        $response = $this
            ->actingAs($user)
            ->get(route('genres.show', $genre));

        $response->assertOk();

        $response->assertViewHas('books', function ($books) {
            return $books->count() === 10
                && $books->total() === 11
                && $books->perPage() === 10;
        });
    }

    public function test_owner_can_delete_unused_genre(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'user_id' => $user->id,
            'name' => '削除可能ジャンル',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas(
            'success',
            'ジャンルを削除しました。'
        );

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
    }

    public function test_other_user_cannot_delete_genre(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $genre = Genre::create([
            'user_id' => $owner->id,
            'name' => '他人は削除不可',
        ]);

        $response = $this
            ->actingAs($otherUser)
            ->delete(route('genres.destroy', $genre));

        $response->assertForbidden();

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_seed_genre_without_owner_cannot_be_deleted(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'user_id' => null,
            'name' => '旅行',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        $response->assertForbidden();

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'user_id' => null,
        ]);
    }

    public function test_owner_cannot_delete_genre_used_by_book(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'user_id' => $user->id,
            'name' => '使用中ジャンル',
        ]);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'テスト用書籍です。',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this
            ->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas(
            'error',
            '書籍に使用されているジャンルは削除できません。'
        );

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);
    }

    public function test_owner_can_update_genre(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'user_id' => $user->id,
            'name' => '更新前ジャンル',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '更新後ジャンル',
            ]);

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '更新後ジャンル',
        ]);
    }

    public function test_other_user_cannot_update_genre(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $genre = Genre::create([
            'user_id' => $owner->id,
            'name' => '他人は更新不可',
        ]);

        $response = $this
            ->actingAs($otherUser)
            ->put(route('genres.update', $genre), [
                'name' => '不正更新',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '他人は更新不可',
        ]);
    }
}