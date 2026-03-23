<?php
// =============================================================
// add_mobbar_employees.php — إضافة موظفي فرع موبار وقطع الغيار
// احذف هذا الملف من السيرفر بعد التنفيذ
// =============================================================
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// البحث عن فرع موبار (يبحث بأي اسم يحتوي على "موبار")
$branch = db()->query("SELECT id, name FROM branches WHERE name LIKE '%موبار%' AND is_active=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$branch) {
    echo '<div style="font-family:sans-serif;direction:rtl;padding:30px;background:#FEE2E2;border:2px solid #EF4444;border-radius:10px;max-width:600px;margin:40px auto">';
    echo '<h2>❌ لم يُعثر على فرع موبار</h2>';
    echo '<p>لا يوجد فرع يحتوي على كلمة "موبار" في قاعدة البيانات.</p>';
    echo '<p>الفروع الموجودة:</p><ul>';
    foreach (db()->query("SELECT id, name FROM branches WHERE is_active=1 ORDER BY name") as $b) {
        echo '<li>#' . $b['id'] . ' — ' . htmlspecialchars($b['name']) . '</li>';
    }
    echo '</ul></div>';
    exit;
}

$branchId   = $branch['id'];
$branchName = $branch['name'];

// قائمة الموظفين: [الاسم، رقم الهاتف، آخر 4 أرقام كـ PIN]
$employees = [
    ['هيثم',       '966551400542', '0542'],
    ['معتز',       '9660475938',   '5938'],
    ['صهيب',       '966558109975', '9975'],
    ['خيري',       '966535115401', '5401'],
    ['عبدو بوية',  '966549601820', '1820'],
    ['احمد',       '966558602267', '2267'],
    ['حسن',        '2614655050',   '5050'],
    ['ابو حازم',   '966560077593', '7593'],
    ['ابو يحيى',   '966500047631', '7631'],
    ['حمادة',      '966560671407', '1407'],
    ['ابراهيم',    '966508873427', '3427'],
];

$results = [];
$addedCount = 0;
$skippedCount = 0;

foreach ($employees as [$name, $phone, $pin]) {
    // تحقق من وجود PIN مسبقاً
    $exists = db()->prepare("SELECT id FROM employees WHERE pin = ?");
    $exists->execute([$pin]);
    if ($exists->fetch()) {
        // PIN مكرر — أضف suffix للـ PIN
        $pin = $pin . '1';
        $exists2 = db()->prepare("SELECT id FROM employees WHERE pin = ?");
        $exists2->execute([$pin]);
        if ($exists2->fetch()) {
            $results[] = ['status' => 'skip', 'name' => $name, 'reason' => 'PIN مكرر حتى بعد التعديل'];
            $skippedCount++;
            continue;
        }
    }

    try {
        // توليد unique_token
        do {
            $token = bin2hex(random_bytes(16));
            $chk = db()->prepare("SELECT id FROM employees WHERE unique_token = ?");
            $chk->execute([$token]);
        } while ($chk->fetch());

        $stmt = db()->prepare("INSERT INTO employees (name, job_title, pin, phone, branch_id, unique_token) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$name, 'موظف', $pin, $phone, $branchId, $token]);
        $newId = db()->lastInsertId();
        $results[] = ['status' => 'ok', 'name' => $name, 'id' => $newId, 'pin' => $pin, 'token' => $token];
        $addedCount++;
    } catch (PDOException $e) {
        $results[] = ['status' => 'error', 'name' => $name, 'reason' => $e->getMessage()];
        $skippedCount++;
    }
}

// عرض النتائج
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إضافة موظفي موبار</title>
<style>
  body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #0F172A; color: #E2E8F0; padding: 30px; }
  .box { max-width: 700px; margin: 0 auto; }
  h2 { color: #10B981; }
  .branch-info { background: #1E293B; border-radius: 10px; padding: 15px; margin-bottom: 20px; border-left: 4px solid #10B981; }
  table { width: 100%; border-collapse: collapse; margin-top: 20px; }
  th { background: #1E3A5F; padding: 10px; text-align: right; }
  td { padding: 10px; border-bottom: 1px solid #334155; }
  .ok { color: #34D399; }
  .skip, .error { color: #F87171; }
  .summary { background: #1E293B; border-radius: 10px; padding: 15px; margin-top: 20px; }
  .token { font-size: .75rem; color: #94A3B8; word-break: break-all; }
  a.btn { display: inline-block; margin-top: 20px; padding: 10px 22px; background: #059669; color: #fff; border-radius: 8px; text-decoration: none; font-weight: bold; }
  .warn { background: #451A03; border: 1px solid #F59E0B; border-radius: 8px; padding: 12px; margin-top: 20px; color: #FCD34D; }
</style>
</head>
<body>
<div class="box">
  <h2>✅ نتائج إضافة الموظفين</h2>
  <div class="branch-info">
    <strong>الفرع:</strong> <?= htmlspecialchars($branchName) ?> (ID: <?= $branchId ?>)
  </div>
  <div class="summary">
    تم إضافة: <strong style="color:#34D399"><?= $addedCount ?></strong> موظف &nbsp;|&nbsp;
    تخطي/خطأ: <strong style="color:#F87171"><?= $skippedCount ?></strong>
  </div>
  <table>
    <tr><th>الاسم</th><th>PIN</th><th>رابط التوكن</th><th>الحالة</th></tr>
    <?php foreach ($results as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['name']) ?></td>
      <td><?= htmlspecialchars($r['pin'] ?? '—') ?></td>
      <td>
        <?php if ($r['status'] === 'ok'): ?>
          <span class="token">employee/attendance.php?token=<?= htmlspecialchars($r['token']) ?></span>
        <?php else: ?>
          <span class="error"><?= htmlspecialchars($r['reason'] ?? '') ?></span>
        <?php endif; ?>
      </td>
      <td class="<?= $r['status'] ?>">
        <?= $r['status'] === 'ok' ? '✅ تمت' : '❌ ' . htmlspecialchars($r['reason'] ?? $r['status']) ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <div class="warn">⚠️ احذف هذا الملف من السيرفر فوراً بعد مراجعة النتائج!</div>
  <a class="btn" href="admin/employees.php">← عرض الموظفين</a>
</div>
</body>
</html>
