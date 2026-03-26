<?php

namespace App\Jobs;

use App\Models\SiteExport;
use App\Services\SiteAnalyticsExcelExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateSiteAnalyticsExportJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $siteExportId) {}

    public function handle(SiteAnalyticsExcelExportService $excel): void
    {
        $export = SiteExport::with(['site', 'user'])->find($this->siteExportId);
        if ($export === null || $export->status !== SiteExport::STATUS_PENDING) {
            return;
        }

        $export->update(['status' => SiteExport::STATUS_PROCESSING]);

        try {
            $excel->build($export);
        } catch (\Throwable $e) {
            $export->update([
                'status' => SiteExport::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
