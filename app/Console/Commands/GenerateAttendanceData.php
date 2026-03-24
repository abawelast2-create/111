<?php

namespace App\Console\Commands;

use App\Services\DataGeneratorService;
use Illuminate\Console\Command;

class GenerateAttendanceData extends Command
{
    protected $signature = 'data:generate
        {--from= : Start date (Y-m-d)}
        {--to= : End date (Y-m-d)}
        {--level=5 : Discipline level 1-10}
        {--scope=all : Scope: all, branch, employee}
        {--id= : Branch ID or Employee ID for scoped generation}';

    protected $description = 'توليد بيانات حضور وانصراف واقعية للاختبار والمحاكاة';

    public function handle(): int
    {
        $from  = $this->option('from') ?? today()->subMonth()->toDateString();
        $to    = $this->option('to') ?? today()->subDay()->toDateString();
        $level = (int) $this->option('level');
        $scope = $this->option('scope');
        $id    = $this->option('id') ? (int) $this->option('id') : null;

        // Preview
        $preview = DataGeneratorService::preview($from, $to, $scope, $id);
        $this->info("المعاينة:");
        $this->table(
            ['الموظفين', 'أيام العمل', 'السجلات المتوقعة', 'الفترة'],
            [[$preview['employees'], $preview['work_days'], $preview['estimated_records'], $preview['date_range']]]
        );
        $this->info("المستوى: {$level}/10");

        if (!$this->confirm('هل تريد المتابعة؟')) {
            return self::SUCCESS;
        }

        $this->info('جارٍ التوليد...');

        $result = DataGeneratorService::generate($from, $to, $level, $scope, $id);

        if (isset($result['error'])) {
            $this->error($result['error']);
            return self::FAILURE;
        }

        $this->info("تم التوليد بنجاح!");
        $this->table(
            ['المعرف', 'الموظفين', 'أيام العمل', 'إجمالي السجلات', 'حضور', 'انصراف', 'إضافي', 'غياب', 'تأخير', 'GPS مزيف'],
            [[$result['batch_id'], $result['employees'], $result['work_days'], $result['total_records'],
              $result['checkins'], $result['checkouts'], $result['overtime'],
              $result['absences'], $result['late_records'], $result['mock_gps']]]
        );

        return self::SUCCESS;
    }
}
