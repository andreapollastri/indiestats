<?php

namespace App\Console\Commands;

use App\Models\OutboundClick;
use App\Models\PageView;
use App\Models\TrackingEvent;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;

class PruneAnalyticsCommand extends Command
{
    protected $signature = 'analytics:prune';

    protected $description = 'Rimuove pageview, eventi di tracking e click in uscita più vecchi del periodo di conservazione configurato';

    public function handle(): int
    {
        $cutoff = $this->retentionCutoff();

        $pv = PageView::query()->where('created_at', '<', $cutoff)->delete();
        $out = OutboundClick::query()->where('created_at', '<', $cutoff)->delete();
        $ev = TrackingEvent::query()->where('created_at', '<', $cutoff)->delete();

        $this->info("Eliminati {$pv} pageview, {$out} click in uscita e {$ev} eventi anteriori a {$cutoff->toDateTimeString()}.");

        return self::SUCCESS;
    }

    private function retentionCutoff(): CarbonInterface
    {
        $days = max(1, (int) config('analytics.retention_days', 375));

        return now()->subDays($days);
    }
}
