<?php

namespace App\Console\Commands;

use App\Models\OutboundClick;
use App\Models\PageView;
use Illuminate\Console\Command;

class PruneAnalyticsCommand extends Command
{
    protected $signature = 'analytics:prune';

    protected $description = 'Rimuove pageview e click in uscita più vecchi del periodo di conservazione configurato';

    public function handle(): int
    {
        $days = max(1, (int) config('analytics.retention_days', 365));
        $cutoff = now()->subDays($days);

        $pv = PageView::query()->where('created_at', '<', $cutoff)->delete();
        $out = OutboundClick::query()->where('created_at', '<', $cutoff)->delete();

        $this->info("Eliminati {$pv} pageview e {$out} click in uscita anteriori a {$cutoff->toDateTimeString()}.");

        return self::SUCCESS;
    }
}
