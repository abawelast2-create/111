<?php
// =============================================================
// api/submit-report.php - استلام التقارير السرية من الموظفين
// يدعم: نص + صور (كاميرا/معرض) + رسائل صوتية (مع تغيير الصوت)
// =============================================================

header('Content-Type: application/json; charset=utf-8');
header('Alt-Svc: clear');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/rate_limiter.php';

// Rate Limiting: 10 تقارير/ساعة لكل IP
if (isRateLimited(10, 3600, 'report')) { rateLimitResponse(); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Method not allowed']);
  exit;
}

$token = trim($_POST['token'] ?? '');
if (empty($token)) {
  echo json_encode(['success' => false, 'message' => 'Token required']);
  exit;
}

$employee = getEmployeeByToken($token);
if (!$employee) {
  echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
  exit;
}

$reportText  = trim($_POST['report_text'] ?? '');
$reportType  = trim($_POST['report_type'] ?? 'general');
$voiceEffect = trim($_POST['voice_effect'] ?? 'none');

// Validate report type
$allowedTypes = ['general', 'violation', 'harassment', 'theft', 'safety', 'other'];
if (!in_array($reportType, $allowedTypes)) {
  $reportType = 'general';
}

if (empty($reportText) && empty($_FILES['images']) && empty($_FILES['voice'])) {
  echo json_encode(['success' => false, 'message' => 'يجب إدخال نص أو إرفاق صورة أو رسالة صوتية']);
  exit;
}

$uploadBase = __DIR__ . '/../uploads/reports';
$imagePaths = [];
$voicePath  = null;

// ── Handle image uploads (multiple) ──
if (!empty($_FILES['images']['name'][0])) {
  $imageDir = $uploadBase . '/images';
  if (!is_dir($imageDir)) mkdir($imageDir, 0755, true);

  $maxImages = 5;
  $maxSize   = 10 * 1024 * 1024; // 10MB per image
  $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

  $fileCount = min(count($_FILES['images']['name']), $maxImages);

  for ($i = 0; $i < $fileCount; $i++) {
    if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
    if ($_FILES['images']['size'][$i] > $maxSize) continue;

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['images']['tmp_name'][$i]);
    if (!in_array($mime, $allowedMime)) continue;

    $mimeToExt = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
    $ext      = $mimeToExt[$mime] ?? 'jpg';
    $filename = 'img_' . uniqid() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destPath = $imageDir . '/' . $filename;

    if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $destPath)) {
      $imagePaths[] = 'uploads/reports/images/' . $filename;
    }
  }
}

// ── Handle voice upload ──
if (!empty($_FILES['voice']) && $_FILES['voice']['error'] === UPLOAD_ERR_OK) {
  $voiceDir = $uploadBase . '/voices';
  if (!is_dir($voiceDir)) mkdir($voiceDir, 0755, true);

  $maxVoiceSize = 15 * 1024 * 1024; // 15MB
  $allowedVoiceMime = ['audio/webm', 'audio/ogg', 'audio/wav', 'audio/mp4', 'audio/mpeg', 'audio/mp3'];

  if ($_FILES['voice']['size'] <= $maxVoiceSize) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $voiceMime = $finfo->file($_FILES['voice']['tmp_name']);
    if (in_array($voiceMime, $allowedVoiceMime)) {
      $ext = 'webm';
      $voiceFilename = 'voice_' . uniqid() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
      $voiceDest = $voiceDir . '/' . $voiceFilename;

      if (move_uploaded_file($_FILES['voice']['tmp_name'], $voiceDest)) {
        $voicePath = 'uploads/reports/voices/' . $voiceFilename;
      }
    }
  }
}

// ── Save to database ──
try {
  $stmt = db()->prepare("
        INSERT INTO secret_reports (employee_id, report_type, report_text, image_paths, voice_path, voice_effect, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'new', NOW())
    ");
  $stmt->execute([
    $employee['id'],
    $reportType,
    $reportText,
    !empty($imagePaths) ? json_encode($imagePaths) : null,
    $voicePath,
    $voiceEffect,
  ]);

  $reportId = db()->lastInsertId();

  echo json_encode([
    'success' => true,
    'message' => 'تم إرسال التقرير بنجاح. شكراً لمساهمتك.',
    'report_id' => $reportId
  ]);
} catch (PDOException $e) {
  error_log("Submit report error: " . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'حدث خطأ في حفظ التقرير']);
}
