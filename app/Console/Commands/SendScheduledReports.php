<?php

namespace App\Console\Commands;

use App\Models\ReportSchedule;
use App\Services\ReportMailService;
use Illuminate\Console\Command;

class SendScheduledReports extends Command
{
    protected $signature = 'reports:send-scheduled';
    protected $description = 'إرسال التقارير المجدولة التي حان وقتها';

    public function handle(): int
    {
        $schedules = ReportSchedule::active()->get();

        $sent = 0;

        foreach ($schedules as $schedule) {
            if (!$schedule->isDueNow()) {
                continue;
            }

            $this->info("إرسال: {$schedule->name}");

            $dates = $this->getDateRange($schedule->frequency);

            $result = ReportMailService::sendReport(
                $schedule->report_type,
                $schedule->recipients,
                $dates['from'],
                $dates['to'],
                $schedule->filters ?? []
            );

            $schedule->update(['last_sent_at' => now()]);

            $this->info("  → تم الإرسال إلى {$result['sent']} من {$result['total']}");

            if (!empty($result['failed'])) {
                foreach ($result['failed'] as $fail) {
                    $this->warn("  ✗ {$fail['email']}: {$fail['error']}");
                }
            }

            $sent++;
        }

        $this->info("اكتمل: {$sent} تقرير تم إرساله");

        return self::SUCCESS;
    }

    private function getDateRange(string $frequency): array
    {
        return match ($frequency) {
            'daily'   => ['from' => today()->subDay()->toDateString(),    'to' => today()->subDay()->toDateString()],
            'weekly'  => ['from' => today()->subWeek()->toDateString(),   'to' => today()->subDay()->toDateString()],
            'monthly' => ['from' => today()->subMonth()->toDateString(),  'to' => today()->subDay()->toDateString()],
            default   => ['from' => today()->subDay()->toDateString(),    'to' => today()->subDay()->toDateString()],
        };
    }
}
