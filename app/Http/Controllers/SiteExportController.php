<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateSiteAnalyticsExportJob;
use App\Models\Site;
use App\Models\SiteExport;
use App\Support\AnalyticsFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiteExportController extends Controller
{
    public function store(Request $request, Site $site): JsonResponse
    {
        $this->authorize('view', $site);

        $validated = $request->validate([
            'range' => 'required|string|in:today,7d,30d,3m,6m,1y',
        ]);

        $filters = AnalyticsFilters::fromRequest($request);

        $export = SiteExport::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $request->user()->id,
            'site_id' => $site->id,
            'status' => SiteExport::STATUS_PENDING,
            'range' => $validated['range'],
            'filters_payload' => $filters->toQueryArray(),
            'expires_at' => now()->addDays(7),
        ]);

        GenerateSiteAnalyticsExportJob::dispatch($export->id)->afterCommit();

        return response()->json([
            'export_uuid' => $export->uuid,
            'status_url' => route('sites.exports.status', [$site, $export]),
        ]);
    }

    public function status(Site $site, SiteExport $export): JsonResponse
    {
        $this->authorize('view', $export);
        $this->abortIfWrongSite($site, $export);

        $export->refresh();

        return response()->json([
            'status' => $export->status,
            'error_message' => $export->error_message,
            'download_url' => $export->isReadyForDownload()
                ? route('sites.exports.download', [$site, $export])
                : null,
        ]);
    }

    public function download(Request $request, Site $site, SiteExport $export): StreamedResponse
    {
        $this->authorize('view', $export);
        $this->abortIfWrongSite($site, $export);

        if (! $export->isReadyForDownload() || $export->file_path === null || $export->file_path === '') {
            abort(404);
        }

        if (! Storage::disk('local')->exists($export->file_path)) {
            abort(404);
        }

        $safeName = 'indiestats-'.Str::slug($site->name).'-'.$export->created_at->format('Y-m-d').'.xlsx';

        return Storage::disk('local')->download($export->file_path, $safeName);
    }

    private function abortIfWrongSite(Site $site, SiteExport $export): void
    {
        if ($export->site_id !== $site->id) {
            abort(404);
        }
    }
}
