<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--type=auto}';
    protected $description = 'إنشاء نسخة احتياطية لقاعدة البيانات';

    public function handle(): int
    {
        $this->info('جاري إنشاء النسخة الاحتياطية...');

        $backup = BackupService::createDatabaseBackup($this->option('type'));

        if ($backup && $backup->status === 'completed') {
            $this->info("تم بنجاح: {$backup->filename} ({$backup->size_formatted})");
            return Command::SUCCESS;
        }

        $this->error('فشل إنشاء النسخة الاحتياطية: ' . ($backup?->notes ?? 'خطأ غير معروف'));
        return Command::FAILURE;
    }
}
