<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use Illuminate\Console\Command;

class ExpireReadingPlans extends Command
{
    protected $signature = 'reading-plans:expire';

    protected $description = '期日を過ぎた読書計画を期限切れに更新します';

    public function handle(): int
    {
        $updatedCount = ReadingPlan::query()
            ->where('status', ReadingPlanStatus::Planned->value)
            ->whereDate('target_date', '<', today())
            ->update([
                'status' => ReadingPlanStatus::Expired->value,
            ]);

        $this->info(
            "{$updatedCount}件の読書計画を期限切れに更新しました。"
        );

        return self::SUCCESS;
    }
}