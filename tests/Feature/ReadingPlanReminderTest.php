<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function createReadingPlan(
        User $user,
        string $targetDate,
        ReadingPlanStatus $status = ReadingPlanStatus::Planned
    ): ReadingPlan {
        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
        ]);

        return ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $targetDate,
            'status' => $status,
        ]);
    }

    public function test_notification_is_created_three_days_before_target_date(): void
    {
        Carbon::setTestNow('2026-09-23 10:00:00');

        $user = User::factory()->create();

        $readingPlan = $this->createReadingPlan(
            $user,
            '2026-09-26'
        );

        $this->artisan('reading-plans:send-reminders')
            ->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
        ]);

        $notification = $user->notifications()->first();

        $this->assertSame(
            $readingPlan->id,
            $notification->data['reading_plan_id']
        );

        $this->assertSame(
            'three_days_before',
            $notification->data['timing']
        );
    }

    public function test_notification_is_created_on_target_date(): void
    {
        Carbon::setTestNow('2026-09-23 10:00:00');

        $user = User::factory()->create();

        $readingPlan = $this->createReadingPlan(
            $user,
            '2026-09-23'
        );

        $this->artisan('reading-plans:send-reminders')
            ->assertSuccessful();

        $notification = $user->notifications()->first();

        $this->assertNotNull($notification);

        $this->assertSame(
            $readingPlan->id,
            $notification->data['reading_plan_id']
        );

        $this->assertSame(
            'on_due_date',
            $notification->data['timing']
        );
    }

    public function test_notification_is_created_three_days_after_target_date(): void
    {
        Carbon::setTestNow('2026-09-23 10:00:00');

        $user = User::factory()->create();

        $readingPlan = $this->createReadingPlan(
            $user,
            '2026-09-20',
            ReadingPlanStatus::Expired
        );

        $this->artisan('reading-plans:send-reminders')
            ->assertSuccessful();

        $notification = $user->notifications()->first();

        $this->assertNotNull($notification);

        $this->assertSame(
            $readingPlan->id,
            $notification->data['reading_plan_id']
        );

        $this->assertSame(
            'three_days_after',
            $notification->data['timing']
        );
    }

    public function test_notification_is_not_created_on_unrelated_date(): void
    {
        Carbon::setTestNow('2026-09-23 10:00:00');

        $user = User::factory()->create();

        $this->createReadingPlan(
            $user,
            '2026-10-10'
        );

        $this->artisan('reading-plans:send-reminders')
            ->assertSuccessful();

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_notification_is_not_created_for_completed_plan(): void
    {
        Carbon::setTestNow('2026-09-23 10:00:00');

        $user = User::factory()->create();

        $this->createReadingPlan(
            $user,
            '2026-09-26',
            ReadingPlanStatus::Completed
        );

        $this->artisan('reading-plans:send-reminders')
            ->assertSuccessful();

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_same_notification_is_not_created_twice(): void
    {
        Carbon::setTestNow('2026-09-23 10:00:00');

        $user = User::factory()->create();

        $this->createReadingPlan(
            $user,
            '2026-09-26'
        );

        $this->artisan('reading-plans:send-reminders')
            ->assertSuccessful();

        $this->artisan('reading-plans:send-reminders')
            ->assertSuccessful();

        $this->assertDatabaseCount('notifications', 1);
    }
}