<?php
// migrate-reports-v2.php - تحديث جدول secret_reports
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$pdo = db();
$cols = $pdo->query("DESCRIBE secret_reports")->fetchAll(PDO::FETCH_COLUMN);
echo "Existing columns: " . implode(', ', $cols) . "\n";

try {
  if (!in_array('image_paths', $cols)) {
    $pdo->exec("ALTER TABLE secret_reports ADD COLUMN image_paths JSON NULL AFTER report_type");
    echo "Added image_paths\n";
  }
  if (!in_array('reviewed_by', $cols)) {
    $pdo->exec("ALTER TABLE secret_reports ADD COLUMN reviewed_by INT NULL AFTER reviewed_at");
    echo "Added reviewed_by\n";
  }
  $pdo->exec("ALTER TABLE secret_reports MODIFY COLUMN status ENUM('new','reviewed','in_progress','resolved','dismissed','archived') DEFAULT 'new'");
  echo "Updated status enum\n";
  echo "Done!\n";
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
}
