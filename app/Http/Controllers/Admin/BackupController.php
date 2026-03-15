<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    /**
     * عرض صفحة النسخ الاحتياطي
     */
    public function index()
    {
        $backups = BackupService::list();
        return view('admin.backups', compact('backups'));
    }

    /**
     * إنشاء نسخة احتياطية يدوية
     */
    public function create()
    {
        $backup = BackupService::createDatabaseBackup('manual');

        if ($backup && $backup->status === 'completed') {
            return back()->with('success', 'تم إنشاء النسخة الاحتياطية بنجاح (' . $backup->size_formatted . ')');
        }

        return back()->with('error', 'فشل إنشاء النسخة الاحتياطية');
    }

    /**
     * تحميل نسخة احتياطية
     */
    public function download(Backup $backup)
    {
        if (!file_exists($backup->path)) {
            return back()->with('error', 'ملف النسخة الاحتياطية غير موجود');
        }

        return response()->download($backup->path, $backup->filename);
    }

    /**
     * حذف نسخة احتياطية
     */
    public function destroy(Backup $backup)
    {
        if (file_exists($backup->path)) {
            unlink($backup->path);
        }
        $backup->delete();

        return back()->with('success', 'تم حذف النسخة الاحتياطية');
    }
}
