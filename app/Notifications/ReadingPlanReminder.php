<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminder extends Notification
{
    use Queueable;

    public function __construct(
        private ReadingPlan $readingPlan,
        private string $timing
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $bookTitle = $this->readingPlan->book->title;
        $targetDate = $this->readingPlan->target_date->format('Y年m月d日');

        return [
            'reading_plan_id' => $this->readingPlan->id,
            'timing' => $this->timing,
            'title' => $this->title(),
            'body' => $this->body($bookTitle, $targetDate),
        ];
    }

    private function title(): string
    {
        return match ($this->timing) {
            'three_days_before' => '読書期日の3日前です',
            'on_due_date' => '読書期日になりました',
            'three_days_after' => '読書期日を3日過ぎています',
            default => '読書計画のお知らせ',
        };
    }

    private function body(
        string $bookTitle,
        string $targetDate
    ): string {
        return match ($this->timing) {
            'three_days_before' =>
                "「{$bookTitle}」の読書期日は{$targetDate}です。",

            'on_due_date' =>
                "「{$bookTitle}」は今日が読書期日です。",

            'three_days_after' =>
                "「{$bookTitle}」の読書期日{$targetDate}を3日過ぎました。",

            default =>
                "「{$bookTitle}」の読書計画を確認してください。",
        };
    }
}