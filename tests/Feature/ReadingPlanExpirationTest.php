<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanExpirationTest extends TestCase
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

    public function test_past_planned_reading_plan_becomes_expired(): void
    {
        Carbon::setTestNow('2026-09-23 10:00:00');

        $user = User::factory()->create();

        $readingPlan = $this->createReadingPlan(
            $user,
            '2026-09-22'
        );

        $this->artisan('reading-plans:expire')
            ->assertSuccessful();

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $readingPlan->status
        );
    }

    public function test_todays_planned_reading_plan_does_not_expire(): void
    {
        Carbon::setTestNow('2026-09-23 10:00:00');

        $user = User::factory()->create();

        $readingPlan = $this->createReadingPlan(
            $user,
            '2026-09-23'
        );

        $this->artisan('reading-plans:expire')
            ->assertSuccessful();

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Planned,
            $readingPlan->status
        );
    }

    public function test_completed_reading_plan_does_not_become_expired(): void
    {
        Carbon::setTestNow('2026-09-23 10:00:00');

        $user = User::factory()->create();

        $readingPlan = $this->createReadingPlan(
            $user,
            '2026-09-22',
            ReadingPlanStatus::Completed
        );

        $this->artisan('reading-plans:expire')
            ->assertSuccessful();

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $readingPlan->status
        );
    }
}