<?php

namespace App\Services;

use App\Models\SiteExport;
use App\Support\AnalyticsFilters;
use App\Support\UserAnalyticsRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SiteAnalyticsExcelExportService
{
    public function __construct(
        private SiteAnalyticsExportDataset $dataset
    ) {}

    public function build(SiteExport $export): void
    {
        ini_set('memory_limit', '512M');

        $site = $export->site;
        $user = $export->user;
        if ($site === null || $user === null) {
            throw new \RuntimeException('Export site or user missing.');
        }

        $query = array_merge($export->filters_payload ?? [], ['range' => $export->range]);
        $request = Request::create('/', 'GET', $query);
        $request->setUserResolver(static fn () => $user);
        $bounds = UserAnalyticsRange::fromRequest($request, $export->range);
        $from = $bounds['from'];
        $to = $bounds['to'];
        $filters = AnalyticsFilters::fromQueryArray($export->filters_payload ?? []);
        $tz = $user->timezone ?? 'UTC';
        $siteId = $site->id;

        $spreadsheet = new Spreadsheet;
        $info = $spreadsheet->getActiveSheet();
        $info->setTitle($this->sheetTitle('Info'));
        $this->fillInfoSheet($info, $site->name, $from->toDateString(), $to->toDateString(), $export->range, $filters);

        $pageTypes = [
            'paths',
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_term',
            'utm_content',
            'search',
            'source',
            'browser',
            'device',
            'os',
            'country',
        ];

        $titles = [
            'paths' => 'Pagine',
            'utm_source' => 'UTM source',
            'utm_medium' => 'UTM medium',
            'utm_campaign' => 'UTM campaign',
            'utm_term' => 'UTM term',
            'utm_content' => 'UTM content',
            'search' => 'Query ricerca',
            'source' => 'Sorgenti',
            'browser' => 'Browser',
            'device' => 'Dispositivo',
            'os' => 'Sistema operativo',
            'country' => 'Paese',
        ];

        foreach ($pageTypes as $type) {
            $data = $this->dataset->pageAggregatedSheet($siteId, $from, $to, $type, $filters);
            $sheet = new Worksheet($spreadsheet, $this->sheetTitle($titles[$type]));
            $spreadsheet->addSheet($sheet);
            $this->fillDataSheet($sheet, $data);
        }

        $out = $this->dataset->outboundSheet($siteId, $from, $to, $filters);
        $sheetOut = new Worksheet($spreadsheet, $this->sheetTitle('Link in uscita'));
        $spreadsheet->addSheet($sheetOut);
        $this->fillDataSheet($sheetOut, $out);

        $evNames = $this->dataset->eventNamesSheet($siteId, $from, $to, $filters);
        $sheetEv = new Worksheet($spreadsheet, $this->sheetTitle('Nomi eventi'));
        $spreadsheet->addSheet($sheetEv);
        $this->fillDataSheet($sheetEv, $evNames);

        $evDetail = $this->dataset->trackingEventsSheet($siteId, $from, $to, $filters, $tz);
        $truncated = $evDetail['truncated'] ?? false;
        $evRows = $evDetail['rows'];
        if ($truncated) {
            $evRows = array_merge(
                [['NOTA: export limitato alle prime '.SiteAnalyticsExportDataset::MAX_TRACKING_EVENT_ROWS.' righe.', '', '', '', '']],
                $evRows
            );
        }
        $sheetDet = new Worksheet($spreadsheet, $this->sheetTitle('Dettaglio eventi'));
        $spreadsheet->addSheet($sheetDet);
        $this->fillDataSheet($sheetDet, [
            'header' => $evDetail['header'],
            'rows' => $evRows,
        ]);

        $goals = $this->dataset->goalsSheet($siteId, $from, $to);
        $sheetGoals = new Worksheet($spreadsheet, $this->sheetTitle('Obiettivi'));
        $spreadsheet->addSheet($sheetGoals);
        $this->fillDataSheet($sheetGoals, $goals);

        Storage::disk('local')->makeDirectory('exports');
        $relative = 'exports/'.$export->uuid.'.xlsx';
        $fullPath = Storage::disk('local')->path($relative);

        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        $export->update([
            'status' => SiteExport::STATUS_COMPLETED,
            'file_path' => $relative,
            'completed_at' => now(),
        ]);
    }

    /**
     * @param  array{header: list<string>, rows: list<list<string|int|float>>}  $data
     */
    private function fillDataSheet(Worksheet $sheet, array $data): void
    {
        $rows = array_merge([$data['header']], $data['rows']);
        $sheet->fromArray($rows, null, 'A1', true);
    }

    private function fillInfoSheet(
        Worksheet $sheet,
        string $siteName,
        string $from,
        string $to,
        string $range,
        AnalyticsFilters $filters
    ): void {
        $lines = [
            ['Sito', $siteName],
            ['Periodo da', $from],
            ['Periodo a', $to],
            ['Intervallo', $range],
        ];
        $fa = $filters->toQueryArray();
        if ($fa === []) {
            $lines[] = ['Filtri', 'nessuno'];
        } else {
            $lines[] = ['Filtri', ''];
            foreach ($fa as $k => $v) {
                $lines[] = [$k, $v];
            }
        }
        $sheet->fromArray($lines, null, 'A1', true);
    }

    private function sheetTitle(string $name): string
    {
        $invalid = ['\\', '/', '?', '*', '[', ']', ':'];
        $name = str_replace($invalid, '', $name);

        return mb_substr($name, 0, 31);
    }
}
