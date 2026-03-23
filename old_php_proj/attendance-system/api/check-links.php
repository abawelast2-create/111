<?php
// =============================================================
// api/check-links.php - فحص حالة جميع روابط الموظفين
// =============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdminLogin();
header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = db()->query("
        SELECT e.id, e.name, e.unique_token, e.is_active
        FROM employees e
        WHERE e.deleted_at IS NULL
        ORDER BY e.name
    ");
    $employees = $stmt->fetchAll();
    
    $results = [];
    $baseUrl = SITE_URL . '/employee/attendance.php?token=';
    
    foreach ($employees as $emp) {
        $link = $baseUrl . $emp['unique_token'];
        $status = 'unknown';
        $statusCode = 0;
        
        // فحص الرابط عبر HTTP HEAD request
        $ch = curl_init($link);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'LinkChecker/1.0'
        ]);
        
        $response = curl_exec($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            $status = 'error';
        } elseif ($statusCode >= 200 && $statusCode < 400) {
            $status = $emp['is_active'] ? 'ok' : 'inactive';
        } elseif ($statusCode === 404) {
            $status = 'not_found';
        } else {
            $status = 'error';
        }
        
        $results[] = [
            'id'     => $emp['id'],
            'status' => $status,
            'code'   => $statusCode,
            'active' => (bool)$emp['is_active']
        ];
    }
    
    jsonResponse([
        'success' => true,
        'results' => $results,
        'total'   => count($results),
        'ok'      => count(array_filter($results, fn($r) => $r['status'] === 'ok')),
        'errors'  => count(array_filter($results, fn($r) => $r['status'] === 'error' || $r['status'] === 'not_found'))
    ]);
    
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'حدث خطأ: ' . $e->getMessage()
    ], 500);
}
