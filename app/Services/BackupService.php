<?php

namespace App\Services;

use App\Models\Backup;
use Illuminate\Support\Facades\Log;

class BackupService
{
    /**
     * إنشاء نسخة احتياطية لقاعدة البيانات
     */
    public static function createDatabaseBackup(string $type = 'auto'): ?Backup
    {
        $filename = 'backup_' . date('Y-m-d_His') . '.sql';
        $directory = storage_path('app/backups');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $path = $directory . '/' . $filename;

        $backup = Backup::create([
            'filename'   => $filename,
            'path'       => $path,
            'type'       => $type,
            'status'     => 'pending',
        ]);

        try {
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port', 3306);
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');

            $command = sprintf(
                'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s',
                escapeshellarg($host),
                escapeshellarg((string) $port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($path)
            );

            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                // بديل: تصدير عبر Laravel
                self::exportViaLaravel($path, $database);
            }

            $size = file_exists($path) ? filesize($path) : 0;

            $backup->update([
                'status'     => 'completed',
                'size_bytes' => $size,
            ]);

            // حذف النسخ القديمة (الاحتفاظ بآخر 30 نسخة)
            self::cleanOldBackups(30);

            return $backup;
        } catch (\Exception $e) {
            $backup->update([
                'status' => 'failed',
                'notes'  => $e->getMessage(),
            ]);
            Log::error('Backup failed: ' . $e->getMessage());
            return $backup;
        }
    }

    /**
     * تصدير قاعدة البيانات عبر Laravel
     */
    private static function exportViaLaravel(string $path, string $database): void
    {
        $tables = \DB::select('SHOW TABLES');
        $key = 'Tables_in_' . $database;

        $sql = "-- Attendance System Backup\n";
        $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $tableName = $table->$key;
            $createTable = \DB::select("SHOW CREATE TABLE `{$tableName}`");
            $sql .= $createTable[0]->{'Create Table'} . ";\n\n";

            $rows = \DB::table($tableName)->get();
            foreach ($rows as $row) {
                $values = array_map(function ($val) {
                    if ($val === null) return 'NULL';
                    return "'" . addslashes((string) $val) . "'";
                }, (array) $row);
                $sql .= "INSERT INTO `{$tableName}` VALUES (" . implode(',', $values) . ");\n";
            }
            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($path, $sql);
    }

    /**
     * حذف النسخ الاحتياطية القديمة
     */
    public static function cleanOldBackups(int $keep = 30): void
    {
        $oldBackups = Backup::where('status', 'completed')
            ->orderByDesc('created_at')
            ->skip($keep)
            ->take(100)
            ->get();

        foreach ($oldBackups as $backup) {
            if (file_exists($backup->path)) {
                unlink($backup->path);
            }
            $backup->delete();
        }
    }

    /**
     * استعادة نسخة احتياطية
     */
    public static function restore(Backup $backup): bool
    {
        if (!file_exists($backup->path)) {
            return false;
        }

        try {
            $sql = file_get_contents($backup->path);
            \DB::unprepared($sql);
            return true;
        } catch (\Exception $e) {
            Log::error('Backup restore failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * قائمة النسخ الاحتياطية
     */
    public static function list(): \Illuminate\Database\Eloquent\Collection
    {
        return Backup::orderByDesc('created_at')->get();
    }
}
