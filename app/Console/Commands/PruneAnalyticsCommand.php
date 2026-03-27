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

    protected $description = 'Deletes page views, tracking events, and outbound clicks older than the configured retention period';

    public function handle(): int
    {
        $cutoff = $this->retentionCutoff();

        $pv = PageView::query()->where('created_at', '<', $cutoff)->delete();
        $out = OutboundClick::query()->where('created_at', '<', $cutoff)->delete();
        $ev = TrackingEvent::query()->where('created_at', '<', $cutoff)->delete();

        $this->info("Deleted {$pv} page views, {$out} outbound clicks, and {$ev} events older than {$cutoff->toDateTimeString()}.");

        return self::SUCCESS;
    }

    private function retentionCutoff(): CarbonInterface
    {
        $days = max(1, (int) config('analytics.retention_days', 375));

        return now()->subDays($days);
    }
}
