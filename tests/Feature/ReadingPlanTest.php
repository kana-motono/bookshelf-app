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
            'status' => ReadingPlanStatus::Planned,
        ]);

        ReadingPlan::create([
            'user_id' => $otherUser->id,
            'book_id' => $otherBook->id,
            'target_date' => now()->addDays(7)->toDateString(),
            'status' => ReadingPlanStatus::Planned,
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
            'status' => ReadingPlanStatus::Planned->value,
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
            'status' => ReadingPlanStatus::Planned,
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
            'status' => ReadingPlanStatus::Planned,
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
            'status' => ReadingPlanStatus::Planned,
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
            'status' => ReadingPlanStatus::Planned,
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
            'status' => ReadingPlanStatus::Planned,
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
            'status' => ReadingPlanStatus::Planned,
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
            'status' => ReadingPlanStatus::Planned,
        ]);

        $response = $this
            ->actingAs($otherUser)
            ->post(route('reading-plans.complete', $readingPlan));

        $response->assertForbidden();

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Planned,
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
            'status' => ReadingPlanStatus::Planned,
        ]);

        $response = $this
            ->actingAs($otherUser)
            ->delete(route('reading-plans.destroy', $readingPlan));

        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
        ]);
    }
}
