<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Console\Command;

class SendReadingPlanReminders extends Command
{
    protected $signature = 'reading-plans:send-reminders';

    protected $description = '読書計画の期日に応じてリマインダー通知を作成します';

    public function handle(): int
    {
        $readingPlans = ReadingPlan::with(['user', 'book'])
            ->where('status', '!=', ReadingPlanStatus::Completed->value)
            ->get();

        $sentCount = 0;

        foreach ($readingPlans as $readingPlan) {
            $timing = $this->getTiming($readingPlan);

            if ($timing === null) {
                continue;
            }

            if ($this->alreadyNotified($readingPlan, $timing)) {
                continue;
            }

            $readingPlan->user->notify(
                new ReadingPlanReminder($readingPlan, $timing)
            );

            $sentCount++;
        }

        $this->info("{$sentCount}件のリマインダー通知を作成しました。");

        return self::SUCCESS;
    }

    private function getTiming(ReadingPlan $readingPlan): ?string
    {
        $today = today();
        $targetDate = $readingPlan->target_date->copy()->startOfDay();

        if ($targetDate->isSameDay($today->copy()->addDays(3))) {
            return 'three_days_before';
        }

        if ($targetDate->isSameDay($today)) {
            return 'on_due_date';
        }

        if ($targetDate->isSameDay($today->copy()->subDays(3))) {
            return 'three_days_after';
        }

        return null;
    }

    private function alreadyNotified(
        ReadingPlan $readingPlan,
        string $timing
    ): bool {
        return $readingPlan->user
            ->notifications()
            ->where('data->reading_plan_id', $readingPlan->id)
            ->where('data->timing', $timing)
            ->exists();
    }
}