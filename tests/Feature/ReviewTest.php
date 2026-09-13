<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
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
            'description' => 'レビュー機能テスト用の書籍です。',
        ]);
    }

    public function test_authenticated_user_can_post_review(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $response = $this
            ->actingAs($user)
            ->post(route('reviews.store', $book), [
                'rating' => 5,
                'comment' => 'とても良い本でした。',
            ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            'レビューを投稿しました。'
        );

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても良い本でした。',
        ]);
    }

    public function test_review_rating_is_required(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $response = $this
            ->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('reviews.store', $book), [
                'comment' => '評価なしのレビュー',
            ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHasErrors([
            'rating' => '評価は必須です。',
        ]);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_review_rating_must_be_between_1_and_5(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $response = $this
            ->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('reviews.store', $book), [
                'rating' => 6,
                'comment' => '範囲外テスト',
            ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHasErrors([
            'rating' => '評価は5以下で入力してください。',
        ]);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_owner_can_update_review(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $review = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '更新前',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('reviews.update', $review), [
                'rating' => 5,
                'comment' => '更新後',
            ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            'レビューを更新しました。'
        );

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => '更新後',
        ]);
    }

    public function test_other_user_cannot_update_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = $this->createBook($owner);

        $review = Review::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '元のコメント',
        ]);

        $response = $this
            ->actingAs($otherUser)
            ->put(route('reviews.update', $review), [
                'rating' => 5,
                'comment' => '不正な更新',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '元のコメント',
        ]);
    }

    public function test_owner_can_delete_review(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $review = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '削除対象レビュー',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('reviews.destroy', $review));

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            'レビューを削除しました。'
        );

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_other_user_cannot_delete_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = $this->createBook($owner);

        $review = Review::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '削除してはいけないレビュー',
        ]);

        $response = $this
            ->actingAs($otherUser)
            ->delete(route('reviews.destroy', $review));

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_authenticated_user_can_like_and_unlike_review(): void
    {
        $reviewOwner = User::factory()->create();
        $user = User::factory()->create();

        $book = $this->createBook($reviewOwner);

        $review = Review::create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'いいね対象レビュー',
        ]);

        $this
            ->actingAs($user)
            ->post(route('reviews.like', $review))
            ->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $this
            ->actingAs($user)
            ->post(route('reviews.like', $review))
            ->assertRedirect(route('books.show', $book));

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_guest_cannot_like_review(): void
    {
        $reviewOwner = User::factory()->create();
        $book = $this->createBook($reviewOwner);

        $review = Review::create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'ゲストいいねテスト',
        ]);

        $response = $this->post(route('reviews.like', $review));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('review_likes', [
            'review_id' => $review->id,
        ]);
    }
}