<?php
/**
 * مراقب التغييرات - Monitoring Script
 * 
 * هذا السكريبت يسجل التغييرات على الملفات
 * يمكن تشغيله دورياً لمراقبة النظام
 */

date_default_timezone_set('Asia/Riyadh');

// ملف السجل
$log_file = __DIR__ . '/MONITORING_LOG.txt';

// دالة لتسجيل التغيير
function logChange($type, $file, $details = '') {
    global $log_file;
    
    $timestamp = date('Y-m-d H:i:s');
    $separator = "\n" . str_repeat('-', 80) . "\n\n";
    
    $entry = "[{$timestamp}] - {$type}\n";
    $entry .= "الملف: {$file}\n";
    if (!empty($details)) {
        $entry .= "التفاصيل: {$details}\n";
    }
    $entry .= "الحالة: تم التسجيل ✓\n";
    $entry .= $separator;
    
    // إضافة للسجل
    file_put_contents($log_file, $entry, FILE_APPEND);
    
    return true;
}

// دالة للحصول على قائمة بجميع الملفات
function getFilesList($dir = '.') {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $files[$file->getPathname()] = [
                'path' => $file->getPathname(),
                'size' => $file->getSize(),
                'modified' => $file->getMTime()
            ];
        }
    }
    
    return $files;
}

// حفظ حالة الملفات الحالية
$snapshot_file = __DIR__ . '/.file_snapshot.json';

// إذا كانت أول مرة، احفظ الحالة الحالية
if (!file_exists($snapshot_file)) {
    $current_files = getFilesList();
    file_put_contents($snapshot_file, json_encode($current_files, JSON_PRETTY_PRINT));
    echo "✓ تم إنشاء Snapshot أولي للملفات\n";
    echo "✓ عدد الملفات المراقبة: " . count($current_files) . "\n";
} else {
    // قارن مع الحالة السابقة
    $previous_files = json_decode(file_get_contents($snapshot_file), true);
    $current_files = getFilesList();
    
    // اكتشف الملفات الجديدة
    foreach ($current_files as $path => $info) {
        if (!isset($previous_files[$path])) {
            logChange('[إضافة] - إنشاء ملف جديد', $path);
            echo "➜ ملف جديد: {$path}\n";
        } elseif ($info['modified'] != $previous_files[$path]['modified']) {
            logChange('[تعديل] - تعديل ملف موجود', $path);
            echo "➜ تم التعديل: {$path}\n";
        }
    }
    
    // اكتشف الملفات المحذوفة
    foreach ($previous_files as $path => $info) {
        if (!isset($current_files[$path])) {
            logChange('[حذف] - حذف ملف', $path);
            echo "➜ تم الحذف: {$path}\n";
        }
    }
    
    // حدّث الـ Snapshot
    file_put_contents($snapshot_file, json_encode($current_files, JSON_PRETTY_PRINT));
    echo "✓ تم تحديث Snapshot\n";
}

echo "\n✓ المراقبة مكتملة - " . date('Y-m-d H:i:s') . "\n";
echo "✓ للمراقبة المستمرة، قم بتشغيل هذا السكريبت دورياً\n";
?>
