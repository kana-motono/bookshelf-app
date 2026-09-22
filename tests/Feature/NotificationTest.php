<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function createNotification(
        User $user,
        string $title = 'テスト通知'
    ): DatabaseNotification {
        return DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\ReadingPlanReminder',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => [
                'reading_plan_id' => 1,
                'timing' => 'three_days_before',
                'title' => $title,
                'body' => 'テスト通知の本文です。',
            ],
            'read_at' => null,
        ]);
    }

    public function test_guest_cannot_view_notifications(): void
    {
        $response = $this->get('/notifications');

        $response->assertRedirect('/login');
    }

    public function test_user_can_view_own_notifications(): void
    {
        $user = User::factory()->create();

        $this->createNotification(
            $user,
            '自分の通知'
        );

        $response = $this->actingAs($user)
            ->get('/notifications');

        $response->assertOk();
        $response->assertSee('自分の通知');
    }

    public function test_user_cannot_see_another_users_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->createNotification(
            $user,
            '自分の通知'
        );

        $this->createNotification(
            $otherUser,
            '他人の通知'
        );

        $response = $this->actingAs($user)
            ->get('/notifications');

        $response->assertOk();
        $response->assertSee('自分の通知');
        $response->assertDontSee('他人の通知');
    }

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = User::factory()->create();

        $notification = $this->createNotification($user);

        $this->assertNull($notification->read_at);

        $response = $this->actingAs($user)
            ->post(
                route('notifications.read', $notification->id)
            );

        $response->assertRedirect(
            route('notifications.index')
        );

        $notification->refresh();

        $this->assertNotNull($notification->read_at);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $notification = $this->createNotification(
            $otherUser
        );

        $response = $this->actingAs($user)
            ->post(
                route('notifications.read', $notification->id)
            );

        $response->assertNotFound();

        $notification->refresh();

        $this->assertNull($notification->read_at);
    }
}