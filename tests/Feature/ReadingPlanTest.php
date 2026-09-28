<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    private function createBook(User $user, string $title = 'テスト書籍'): Book
    {
        return Book::create([
            'user_id' => $user->id,
            'title' => $title,
            'author' => 'テスト著者',
        ]);
    }

    public function test_guest_cannot_view_reading_plans(): void
    {
        $response = $this->get('/reading-plans');

        $response->assertRedirect('/login');
    }

    public function test_user_can_view_only_own_reading_plans(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownBook = $this->createBook($user, '自分の読書計画');
        $otherBook = $this->createBook($otherUser, '他人の読書計画');

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $ownBook->id,
            'target_date' => now()->addDays(7)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        ReadingPlan::create([
            'user_id' => $otherUser->id,
            'book_id' => $otherBook->id,
            'target_date' => now()->addDays(7)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/reading-plans');

        $response->assertOk();
        $response->assertSee('自分の読書計画');
        $response->assertDontSee('他人の読書計画');
    }

    public function test_user_can_create_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $response = $this
            ->actingAs($user)
            ->post('/reading-plans', [
                'book_id' => $book->id,
                'target_date' => now()->addDays(7)->toDateString(),
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_book_and_target_date_are_required(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post('/reading-plans', []);

        $response->assertSessionHasErrors([
            'book_id',
            'target_date',
        ]);
    }

    public function test_target_date_cannot_be_in_the_past(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $response = $this
            ->actingAs($user)
            ->post('/reading-plans', [
                'book_id' => $book->id,
                'target_date' => now()->subDay()->toDateString(),
            ]);

        $response->assertSessionHasErrors('target_date');

        $this->assertDatabaseMissing('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_same_user_cannot_create_duplicate_plan_for_same_book(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(3)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/reading-plans', [
                'book_id' => $book->id,
                'target_date' => now()->addDays(10)->toDateString(),
            ]);

        $response->assertSessionHasErrors('book_id');

        $this->assertDatabaseCount('reading_plans', 1);
    }

    public function test_user_can_update_own_reading_plan_target_date(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(3)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $newTargetDate = now()->addDays(14)->toDateString();

        $response = $this
            ->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => $newTargetDate,
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => $newTargetDate,
        ]);
    }

    public function test_user_can_complete_own_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(3)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.complete', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $readingPlan->status
        );

        $this->assertNotNull($readingPlan->completed_at);
    }

    public function test_user_can_delete_own_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(3)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('reading-plans.destroy', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $readingPlan->id,
        ]);
    }

    public function test_user_cannot_edit_another_users_reading_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = $this->createBook($owner);

        $readingPlan = ReadingPlan::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(3)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this
            ->actingAs($otherUser)
            ->get(route('reading-plans.edit', $readingPlan));

        $response->assertForbidden();
    }

    public function test_user_cannot_update_another_users_reading_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = $this->createBook($owner);

        $readingPlan = ReadingPlan::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(3)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this
            ->actingAs($otherUser)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => now()->addDays(10)->toDateString(),
            ]);

        $response->assertForbidden();
    }

    public function test_user_cannot_complete_another_users_reading_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = $this->createBook($owner);

        $readingPlan = ReadingPlan::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(3)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this
            ->actingAs($otherUser)
            ->post(route('reading-plans.complete', $readingPlan));

        $response->assertForbidden();

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $readingPlan->status
        );
    }

    public function test_user_cannot_delete_another_users_reading_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = $this->createBook($owner);

        $readingPlan = ReadingPlan::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(3)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this
            ->actingAs($otherUser)
            ->delete(route('reading-plans.destroy', $readingPlan));

        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
        ]);
    }

    public function test_reading_plan_validation_messages_are_correct(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post('/reading-plans', [
                'book_id' => 'abc',
                'target_date' => 'invalid-date',
            ]);

        $response->assertSessionHasErrors([
            'book_id' => '書籍IDは整数で入力してください。',
            'target_date' => '期日は有効な日付形式で入力してください。',
        ]);
    }

    public function test_required_validation_messages_are_correct(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post('/reading-plans', []);

        $response->assertSessionHasErrors([
            'book_id' => '書籍を選択してください。',
            'target_date' => '期日は必須です。',
        ]);
    }

    public function test_past_target_date_validation_message_is_correct(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $response = $this
            ->actingAs($user)
            ->post('/reading-plans', [
                'book_id' => $book->id,
                'target_date' => now()->subDay()->toDateString(),
            ]);

        $response->assertSessionHasErrors([
            'target_date' => '期日は今日以降の日付を指定してください。',
        ]);
    }

    public function test_duplicate_in_progress_plan_has_correct_message(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(3)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/reading-plans', [
                'book_id' => $book->id,
                'target_date' => now()->addDays(10)->toDateString(),
            ]);

        $response->assertSessionHasErrors([
            'book_id' => 'この書籍は既に進行中の読書計画が存在します。',
        ]);
    }

    public function test_user_can_create_new_plan_after_completed_plan(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->subDays(3)->toDateString(),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now()->subDay(),
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/reading-plans', [
                'book_id' => $book->id,
                'target_date' => now()->addDays(7)->toDateString(),
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseCount('reading_plans', 2);
    }

    public function test_expired_reading_plan_can_be_edited(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->subDay()->toDateString(),
            'status' => ReadingPlanStatus::Expired,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.edit', $readingPlan));

        $response->assertOk();
    }

    public function test_completed_reading_plan_cannot_be_edited(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->subDay()->toDateString(),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.edit', $readingPlan));

        $response->assertForbidden();
    }

    public function test_completed_reading_plan_cannot_be_updated(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->subDay()->toDateString(),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => now()->addDays(7)->toDateString(),
            ]);

        $response->assertForbidden();
    }

    public function test_completed_reading_plan_cannot_be_completed_again(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->subDay()->toDateString(),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.complete', $readingPlan));

        $response->assertForbidden();
    }

    public function test_completed_reading_plan_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->subDay()->toDateString(),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('reading-plans.destroy', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $readingPlan->id,
        ]);
    }

    public function test_complete_has_correct_success_message(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(3)->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.complete', $readingPlan));

        $response->assertSessionHas(
            'success',
            '読書計画を完了しました。'
        );
    }
}
