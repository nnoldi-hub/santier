<?php

namespace App\Jobs;

use App\Exports\EnterpriseWorkbookExport;
use App\Mail\ScheduledExportMail;
use App\Models\ExportSubscription;
use App\Support\ExportAudit;
use App\Support\ExportChartBuilder;
use App\Support\ExportDatasetBuilder;
use App\Support\ExportScheduleCalculator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class RunExportSubscriptionJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $subscriptionId)
    {
    }

    public function handle(): void
    {
        $subscription = ExportSubscription::query()->find($this->subscriptionId);

        if (!$subscription || !$subscription->active) {
            return;
        }

        $filters = $subscription->filters ?? [];
        $format = strtolower($subscription->format);
        $exportType = $subscription->export_type;

        $fileName = sprintf('%s_%s.%s', $exportType, now()->format('Ymd_His'), $format);
        $relativePath = 'exports/scheduled/' . $fileName;
        $fullPath = storage_path('app/private/' . $relativePath);

        if ($format === 'xlsx') {
            $workbook = new EnterpriseWorkbookExport([$exportType], $filters, [
                'company_name' => config('exports.company_name'),
                'company_email' => config('exports.company_email'),
                'company_phone' => config('exports.company_phone'),
            ]);

            Excel::store($workbook, $relativePath, 'local');
        } elseif ($format === 'pdf') {
            $dataset = ExportDatasetBuilder::build($exportType, $filters);
            $pdf = Pdf::loadView('exports.managerial-pdf', [
                'title' => 'Raport programat: ' . $subscription->name,
                'branding' => [
                    'company_name' => config('exports.company_name'),
                    'company_email' => config('exports.company_email'),
                    'company_phone' => config('exports.company_phone'),
                    'brand_color' => config('exports.brand_color'),
                ],
                'generatedAt' => now()->toDateTimeString(),
                'filters' => $filters,
                'sections' => [
                    [
                        'name' => ucfirst($exportType),
                        'charts' => ExportChartBuilder::build($exportType, $dataset['rows']),
                        'rows' => $dataset['rows']->map(fn ($row) => is_array($row) ? $row : (method_exists($row, 'toArray') ? $row->toArray() : (array) $row))->values(),
                    ],
                ],
            ]);

            Storage::disk('local')->put($relativePath, $pdf->output());
        } else {
            $dataset = ExportDatasetBuilder::build($exportType, $filters);
            $csv = fopen('php://temp', 'r+');
            $rows = $dataset['rows'];

            if ($rows->isNotEmpty()) {
                $first = $rows->first();
                $firstArray = is_array($first) ? $first : (method_exists($first, 'toArray') ? $first->toArray() : (array) $first);
                fputcsv($csv, array_keys($firstArray));
                foreach ($rows as $row) {
                    $arr = is_array($row) ? $row : (method_exists($row, 'toArray') ? $row->toArray() : (array) $row);
                    fputcsv($csv, array_map(function ($value) {
                        if (is_array($value)) {
                            return json_encode($value, JSON_UNESCAPED_UNICODE);
                        }

                        if (is_object($value)) {
                            return method_exists($value, '__toString')
                                ? (string) $value
                                : json_encode((array) $value, JSON_UNESCAPED_UNICODE);
                        }

                        return $value;
                    }, array_values($arr)));
                }
            } else {
                fputcsv($csv, ['Info']);
                fputcsv($csv, ['Nu exista date pentru filtrele selectate']);
            }

            rewind($csv);
            Storage::disk('local')->put($relativePath, stream_get_contents($csv) ?: '');
            fclose($csv);
        }

        $recipients = $subscription->recipients ?? [];

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->queue(new ScheduledExportMail(
                subscriptionName: $subscription->name,
                exportType: $exportType,
                format: $format,
                filePath: $fullPath,
            ));
        }

        ExportAudit::log($exportType, $format, $filters, [
            'file_name' => $fileName,
            'file_size' => is_file($fullPath) ? filesize($fullPath) : null,
            'status' => 'success',
            'delivery_channel' => 'email',
            'delivery_target' => implode(',', $recipients),
            'notes' => 'Scheduled export: ' . $subscription->name,
        ]);

        $subscription->update([
            'last_run_at' => now(),
            'next_run_at' => ExportScheduleCalculator::nextRunAt($subscription->frequency, $subscription->schedule_time, $subscription->schedule_weekday),
        ]);
    }
}
