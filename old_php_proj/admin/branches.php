<?php
// =============================================================
// admin/branches.php - إدارة الفروع (CRUD + مواعيد + موقع)
// =============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdminLogin();

$pageTitle  = 'إدارة الفروع';
$activePage = 'branches';
$message    = '';
$msgType    = '';

// =================== إجراءات POST ===================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'طلب غير صالح';
        $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        // --- تعديل جماعي ---
        if ($action === 'bulk_edit') {
            $radius   = (int)($_POST['geofence_radius'] ?? 25);
            $allowOT  = (int)($_POST['allow_overtime'] ?? 1);
            $otAfter  = (int)($_POST['overtime_start_after'] ?? 60);
            $otMin    = (int)($_POST['overtime_min_duration'] ?? 30);
            $active   = (int)($_POST['is_active'] ?? 1);

            $stmt = db()->prepare("UPDATE branches SET geofence_radius=?, allow_overtime=?, overtime_start_after=?, overtime_min_duration=?, is_active=?");
            $stmt->execute([$radius, $allowOT, $otAfter, $otMin, $active]);

            // تحديث الوردية الأولى لجميع الفروع
            $shiftStart = sanitize($_POST['shift_1_start'] ?? '12:00');
            $shiftEnd   = sanitize($_POST['shift_1_end'] ?? '16:00');
            $branches_all = db()->query("SELECT id FROM branches")->fetchAll();
            foreach ($branches_all as $br) {
                $chk = db()->prepare("SELECT id FROM branch_shifts WHERE branch_id = ? AND shift_number = 1");
                $chk->execute([$br['id']]);
                if ($chk->fetch()) {
                    db()->prepare("UPDATE branch_shifts SET shift_start=?, shift_end=? WHERE branch_id=? AND shift_number=1")
                        ->execute([$shiftStart, $shiftEnd, $br['id']]);
                } else {
                    db()->prepare("INSERT INTO branch_shifts (branch_id, shift_number, shift_start, shift_end) VALUES (?,1,?,?)")
                        ->execute([$br['id'], $shiftStart, $shiftEnd]);
                }
            }

            $message = "تم تطبيق التعديلات الجماعية على جميع الفروع";
            $msgType = 'success';
        }

        // --- إضافة فرع ---
        if ($action === 'add') {
            $name     = sanitize($_POST['name'] ?? '');
            $lat      = (float)($_POST['latitude'] ?? 0);
            $lon      = (float)($_POST['longitude'] ?? 0);
            $radius   = (int)($_POST['geofence_radius'] ?? 25);
            $allowOT  = (int)($_POST['allow_overtime'] ?? 1);
            $otAfter  = (int)($_POST['overtime_start_after'] ?? 60);
            $otMin    = (int)($_POST['overtime_min_duration'] ?? 30);

            if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
                $message = 'إحداثيات غير صالحة';
                $msgType = 'error';
            } elseif ($radius < 10 || $radius > 10000) {
                $message = 'نصف قطر الجيوفينس يجب أن يكون بين 10 و 10000 متر';
                $msgType = 'error';
            } elseif ($name && $lat != 0 && $lon != 0) {
                try {
                    $stmt = db()->prepare("INSERT INTO branches (name, latitude, longitude, geofence_radius, allow_overtime, overtime_start_after, overtime_min_duration) VALUES (?,?,?,?,?,?,?)");
                    $stmt->execute([$name, $lat, $lon, $radius, $allowOT, $otAfter, $otMin]);
                    $newBranchId = (int)db()->lastInsertId();

                    // إضافة الورديات
                    for ($sn = 1; $sn <= 3; $sn++) {
                        $ss = sanitize($_POST["shift_{$sn}_start"] ?? '');
                        $se = sanitize($_POST["shift_{$sn}_end"] ?? '');
                        if ($ss && $se) {
                            db()->prepare("INSERT INTO branch_shifts (branch_id, shift_number, shift_start, shift_end) VALUES (?,?,?,?)")
                                ->execute([$newBranchId, $sn, $ss, $se]);
                        }
                    }

                    $message = "تم إضافة الفرع «{$name}» بنجاح";
                    $msgType = 'success';
                } catch (PDOException $e) {
                    $message = 'خطأ: ' . ($e->getCode() == 23000 ? 'اسم الفرع مكرر' : $e->getMessage());
                    $msgType = 'error';
                }
            } else {
                $message = 'أدخل اسم الفرع والإحداثيات';
                $msgType = 'error';
            }
        }

        // --- تعديل فرع ---
        if ($action === 'edit') {
            $id       = (int)($_POST['branch_id'] ?? 0);
            $name     = sanitize($_POST['name'] ?? '');
            $lat      = (float)($_POST['latitude'] ?? 0);
            $lon      = (float)($_POST['longitude'] ?? 0);
            $radius   = (int)($_POST['geofence_radius'] ?? 25);
            $allowOT  = (int)($_POST['allow_overtime'] ?? 1);
            $otAfter  = (int)($_POST['overtime_start_after'] ?? 60);
            $otMin    = (int)($_POST['overtime_min_duration'] ?? 30);
            $active   = (int)($_POST['is_active'] ?? 1);

            if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
                $message = 'إحداثيات غير صالحة';
                $msgType = 'error';
            } elseif ($radius < 10 || $radius > 10000) {
                $message = 'نصف قطر الجيوفينس يجب أن يكون بين 10 و 10000 متر';
                $msgType = 'error';
            } elseif ($id && $name) {
                $stmt = db()->prepare("UPDATE branches SET name=?, latitude=?, longitude=?, geofence_radius=?, allow_overtime=?, overtime_start_after=?, overtime_min_duration=?, is_active=? WHERE id=?");
                $stmt->execute([$name, $lat, $lon, $radius, $allowOT, $otAfter, $otMin, $active, $id]);

                // تحديث الورديات
                for ($sn = 1; $sn <= 3; $sn++) {
                    $ss = sanitize($_POST["shift_{$sn}_start"] ?? '');
                    $se = sanitize($_POST["shift_{$sn}_end"] ?? '');
                    if ($ss && $se) {
                        $chk = db()->prepare("SELECT id FROM branch_shifts WHERE branch_id=? AND shift_number=?");
                        $chk->execute([$id, $sn]);
                        if ($chk->fetch()) {
                            db()->prepare("UPDATE branch_shifts SET shift_start=?, shift_end=?, is_active=1 WHERE branch_id=? AND shift_number=?")
                                ->execute([$ss, $se, $id, $sn]);
                        } else {
                            db()->prepare("INSERT INTO branch_shifts (branch_id, shift_number, shift_start, shift_end) VALUES (?,?,?,?)")
                                ->execute([$id, $sn, $ss, $se]);
                        }
                    } else {
                        // حذف الوردية إذا فارغة
                        db()->prepare("DELETE FROM branch_shifts WHERE branch_id=? AND shift_number=?")->execute([$id, $sn]);
                    }
                }

                $message = "تم تحديث الفرع «{$name}»";
                $msgType = 'success';
            }
        }

        // --- حذف فرع ---
        if ($action === 'delete') {
            $id = (int)($_POST['branch_id'] ?? 0);
            if ($id) {
                // تحقق من عدم وجود موظفين
                $empCount = db()->prepare("SELECT COUNT(*) FROM employees WHERE branch_id = ?");
                $empCount->execute([$id]);
                if ((int)$empCount->fetchColumn() > 0) {
                    $message = 'لا يمكن حذف فرع يحتوي على موظفين. انقل الموظفين أولاً.';
                    $msgType = 'error';
                } else {
                    db()->prepare("DELETE FROM branches WHERE id=?")->execute([$id]);
                    $message = "تم حذف الفرع";
                    $msgType = 'success';
                }
            }
        }

        // --- تفعيل/تعطيل ---
        if ($action === 'toggle') {
            $id = (int)($_POST['branch_id'] ?? 0);
            if ($id) {
                db()->prepare("UPDATE branches SET is_active = 1 - is_active WHERE id=?")->execute([$id]);
                $message = "تم تغيير حالة الفرع";
                $msgType = 'success';
            }
        }
    }
    header('Location: branches.php?msg=' . urlencode($message) . '&t=' . $msgType);
    exit;
}

// عرض الرسالة من redirect
if (!empty($_GET['msg'])) {
    $message = htmlspecialchars($_GET['msg']);
    $msgType = $_GET['t'] ?? 'success';
}

// =================== جلب الفروع ===================
$branches = db()->query("SELECT b.*, (SELECT COUNT(*) FROM employees WHERE branch_id = b.id) AS emp_count FROM branches b ORDER BY b.id ASC")->fetchAll();

// جلب الورديات لكل فرع
$allShifts = [];
$shiftRows = db()->query("SELECT branch_id, shift_number, shift_start, shift_end FROM branch_shifts WHERE is_active = 1 ORDER BY branch_id, shift_number")->fetchAll();
foreach ($shiftRows as $sr) {
    $allShifts[$sr['branch_id']][$sr['shift_number']] = $sr;
}

// جلب موظفي كل فرع لأزرار واتساب
$branchEmployees = [];
$empRows = db()->query("SELECT id, name, phone, pin, branch_id FROM employees WHERE is_active = 1 AND deleted_at IS NULL ORDER BY branch_id, name")->fetchAll();
foreach ($empRows as $emp) {
    $branchEmployees[$emp['branch_id']][] = [
        'name'  => $emp['name'],
        'phone' => preg_replace('/[^0-9]/', '', $emp['phone']),
        'pin'   => $emp['pin'],
    ];
}

$csrf = generateCsrfToken();

require_once __DIR__ . '/../includes/admin_layout.php';
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/vendor/leaflet/leaflet.min.css" />
<script src="<?= SITE_URL ?>/assets/vendor/leaflet/leaflet.min.js"></script>
<script>
// إصلاح مسار أيقونات Leaflet
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconUrl: '<?= SITE_URL ?>/assets/vendor/leaflet/images/marker-icon.png',
    iconRetinaUrl: '<?= SITE_URL ?>/assets/vendor/leaflet/images/marker-icon-2x.png',
    shadowUrl: '<?= SITE_URL ?>/assets/vendor/leaflet/images/marker-shadow.png',
});
</script>

<style>
    .branch-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
        gap: 18px;
        margin-bottom: 22px;
    }

    .branch-card {
        background: var(--surface);
        border-radius: var(--radius);
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        overflow: hidden;
        transition: transform .2s, box-shadow .2s;
    }

    .branch-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .branch-card.inactive {
        opacity: .6;
    }

    .bc-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-d));
        padding: 16px 20px;
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .bc-name {
        font-size: 1.1rem;
        font-weight: 700;
    }

    .bc-badge {
        padding: 3px 10px;
        border-radius: 20px;
        font-size: .7rem;
        font-weight: 700;
        background: rgba(255, 255, 255, .2);
        border: 1px solid rgba(255, 255, 255, .3);
    }

    .bc-badge.off {
        background: rgba(0, 0, 0, .3);
    }

    .bc-body {
        padding: 16px 20px;
    }

    .bc-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px 16px;
        margin-bottom: 12px;
    }

    .bc-info {
        font-size: .78rem;
    }

    .bc-label {
        color: var(--text3);
        font-weight: 600;
        margin-bottom: 2px;
    }

    .bc-val {
        font-weight: 700;
        color: var(--text);
    }

    .bc-section {
        font-size: .72rem;
        font-weight: 700;
        color: var(--primary-d);
        margin: 10px 0 6px;
        padding-bottom: 4px;
        border-bottom: 1px solid var(--primary-l);
    }

    .bc-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        padding: 12px 20px;
        border-top: 1px solid var(--border);
        background: var(--surface2);
    }

    /* Modal styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, .5);
        z-index: 1000;
        display: none;
        align-items: center;
        justify-content: center;
    }

    .modal-overlay.show {
        display: flex;
    }

    .modal-branch {
        background: #fff;
        border-radius: 16px;
        width: 95%;
        max-width: 700px;
        max-height: 90vh;
        overflow-y: auto;
        overflow-x: hidden;
        box-shadow: 0 25px 50px rgba(0, 0, 0, .25);
        padding: 0;
        isolation: isolate;
    }

    .modal-branch-head {
        background: linear-gradient(135deg, var(--primary), var(--primary-d));
        padding: 20px 24px;
        color: #fff;
        border-radius: 16px 16px 0 0;
        font-size: 1.1rem;
        font-weight: 700;
    }

    .modal-branch-body {
        padding: 24px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        margin-bottom: 14px;
    }

    .form-row.col3 {
        grid-template-columns: 1fr 1fr 1fr;
    }

    .form-row.col1 {
        grid-template-columns: 1fr;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-label {
        font-size: .78rem;
        font-weight: 600;
        color: var(--text2);
        margin-bottom: 4px;
    }

    .form-control {
        padding: 8px 12px;
        border: 1.5px solid var(--border);
        border-radius: 8px;
        font-family: inherit;
        font-size: .88rem;
        transition: border-color .2s;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(249, 115, 22, .15);
    }

    .form-section {
        font-size: .82rem;
        font-weight: 700;
        color: var(--primary-d);
        margin: 16px 0 8px;
        padding: 6px 0;
        border-bottom: 2px solid var(--primary-l);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .modal-branch-foot {
        padding: 16px 24px;
        border-top: 1px solid var(--border);
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    #branchMapAdd,
    #branchMapEdit {
        width: 100%;
        height: 250px;
        border-radius: 10px;
        margin-bottom: 10px;
        border: 2px solid var(--border);
        overflow: hidden;
        position: relative;
        z-index: 0;
        contain: layout paint;
        isolation: isolate;
    }

    #branchMapAdd .leaflet-container,
    #branchMapEdit .leaflet-container {
        border-radius: 8px;
    }
</style>

<?php if ($message): ?>
    <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>"><?= $message ?></div>
<?php endif; ?>

<div class="card-header" style="margin-bottom:18px">
    <span class="card-title"><span class="card-title-bar"></span> الفروع (<?= count($branches) ?>)</span>
    <button class="btn btn-primary" onclick="openAddModal()">+ إضافة فرع</button>
    <button class="btn btn-secondary" onclick="openBulkEditModal()">🛠️ تعديل جماعي</button>
<!-- =================== Modal التعديل الجماعي =================== -->
<div class="modal-overlay" id="bulkEditModal">
    <div class="modal-branch">
        <div class="modal-branch-head">تعديل جماعي للفروع</div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="bulk_edit">
            <div class="modal-branch-body">
                <div class="form-section">الحقول الموحدة (عدا الاسم والإحداثيات)</div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">نصف قطر النطاق (م)</label>
                        <input class="form-control" type="number" name="geofence_radius" value="25" min="1" required>
                    </div>
                </div>
                <div class="form-section">الوردية الأولى (تُطبّق على جميع الفروع)</div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">بدء الوردية</label>
                        <input class="form-control" type="time" name="shift_1_start" value="12:00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">نهاية الوردية</label>
                        <input class="form-control" type="time" name="shift_1_end" value="16:00">
                    </div>
                </div>
                <div class="form-section">الدوام الإضافي</div>
                <div class="form-row col3">
                    <div class="form-group">
                        <label class="form-label">مسموح</label>
                        <select class="form-control" name="allow_overtime">
                            <option value="1">نعم</option>
                            <option value="0">لا</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">يبدأ بعد (دقيقة)</label>
                        <input class="form-control" type="number" name="overtime_start_after" value="60" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">الحد الأدنى (دقيقة)</label>
                        <input class="form-control" type="number" name="overtime_min_duration" value="30" min="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">الحالة</label>
                        <select class="form-control" name="is_active">
                            <option value="1">مفعّل</option>
                            <option value="0">معطّل</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-branch-foot">
                <button type="button" class="btn btn-secondary" onclick="closeModal('bulkEditModal')">إلغاء</button>
                <button type="submit" class="btn btn-primary">تطبيق على الجميع →</button>
            </div>
        </form>
    </div>
</div>
<script>
function openBulkEditModal() {
    openModal('bulkEditModal');
}
</script>
</div>

<!-- بطاقات الفروع -->
<div class="branch-grid">
    <?php foreach ($branches as $b): ?>
        <div class="branch-card <?= $b['is_active'] ? '' : 'inactive' ?>">
            <div class="bc-header">
                <span class="bc-name"><?= htmlspecialchars($b['name']) ?></span>
                <span class="bc-badge <?= $b['is_active'] ? '' : 'off' ?>">
                    <?= $b['is_active'] ? 'مفعّل' : 'معطّل' ?>
                </span>
            </div>
            <div class="bc-body">
                <div class="bc-info-grid">
                    <div class="bc-info">
                        <div class="bc-label">عدد الموظفين</div>
                        <div class="bc-val"><?= $b['emp_count'] ?></div>
                    </div>
                    <div class="bc-info">
                        <div class="bc-label">نصف القطر</div>
                        <div class="bc-val"><?= $b['geofence_radius'] ?> م</div>
                    </div>
                </div>

                <div class="bc-section">الورديات</div>
                <div class="bc-info-grid">
                    <?php
                    $bShifts = $allShifts[$b['id']] ?? [];
                    for ($sn = 1; $sn <= 3; $sn++):
                        if (isset($bShifts[$sn])):
                    ?>
                    <div class="bc-info">
                        <div class="bc-label">وردية <?= $sn ?></div>
                        <div class="bc-val"><?= htmlspecialchars($bShifts[$sn]['shift_start']) ?> — <?= htmlspecialchars($bShifts[$sn]['shift_end']) ?></div>
                    </div>
                    <?php endif; endfor; ?>
                </div>

                <div class="bc-section">الدوام الإضافي</div>
                <div class="bc-info-grid">
                    <div class="bc-info">
                        <div class="bc-label">مسموح</div>
                        <div class="bc-val"><?= $b['allow_overtime'] ? 'نعم' : 'لا' ?></div>
                    </div>
                    <div class="bc-info">
                        <div class="bc-label">يبدأ بعد</div>
                        <div class="bc-val"><?= $b['overtime_start_after'] ?> دقيقة</div>
                    </div>
                </div>
            </div>
            <div class="bc-actions">
                <a class="btn btn-secondary btn-sm" href="branch-edit.php?id=<?= (int)$b['id'] ?>">تعديل</a>
                <?php $bEmps = $branchEmployees[$b['id']] ?? []; if (!empty($bEmps)): ?>
                <button type="button" class="btn btn-sm" style="background:#25D366;color:#fff;border:none" onclick='openWhatsAppLinks(<?= htmlspecialchars(json_encode($bEmps, JSON_UNESCAPED_UNICODE), ENT_QUOTES, "UTF-8") ?>)'>📱 واتساب (<?= count($bEmps) ?>)</button>
                <?php endif; ?>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="branch_id" value="<?= $b['id'] ?>">
                    <button type="submit" class="btn btn-secondary btn-sm"><?= $b['is_active'] ? 'تعطيل' : 'تفعيل' ?></button>
                </form>
                <?php if ($b['emp_count'] == 0): ?>
                    <form method="POST" style="display:inline" onsubmit="return confirm('حذف الفرع نهائياً؟')">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="branch_id" value="<?= $b['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (empty($branches)): ?>
        <div class="card" style="text-align:center;padding:40px;color:var(--text3)">لا توجد فروع بعد. اضغط "إضافة فرع" لبدء الإعداد.</div>
    <?php endif; ?>
</div>

<!-- =================== Modal إضافة فرع =================== -->
<div class="modal-overlay" id="addModal">
    <div class="modal-branch">
        <div class="modal-branch-head">إضافة فرع جديد</div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="add">
            <div class="modal-branch-body">
                <div class="form-section">بيانات الفرع</div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">اسم الفرع *</label>
                        <input class="form-control" name="name" required placeholder="مثال: صرح الرئيسي">
                    </div>
                    <div class="form-group">
                        <label class="form-label">نصف قطر النطاق (م) *</label>
                        <input class="form-control" type="number" name="geofence_radius" value="25" min="1" required>
                    </div>
                </div>
                <div class="form-section">
                    الموقع الجغرافي — اضغط على الخريطة أو أدخل الإحداثيات يدوياً
                    <button type="button" class="btn btn-secondary btn-sm" onclick="useMyLocation('add')" style="margin-right:auto;font-size:.72rem;padding:3px 10px">📍 موقعي الحالي</button>
                </div>
                <div id="branchMapAdd"></div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">خط العرض *</label>
                        <input class="form-control" type="number" step="any" name="latitude" id="addLat" required style="direction:ltr" placeholder="مثال: 24.7136" oninput="syncMapFromInputs('add')">
                    </div>
                    <div class="form-group">
                        <label class="form-label">خط الطول *</label>
                        <input class="form-control" type="number" step="any" name="longitude" id="addLon" required style="direction:ltr" placeholder="مثال: 46.6753" oninput="syncMapFromInputs('add')">
                    </div>
                </div>

                <div class="form-section">الورديات</div>
                <div style="background:linear-gradient(135deg,#EFF6FF,#DBEAFE);border:1px solid #93C5FD;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:.8rem;color:#1E40AF;line-height:1.6">
                    تسجيل الحضور متاح قبل بدء الوردية بساعة وحتى نهايتها. الانصراف يتم تلقائياً عند انتهاء الوردية.
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">وردية 1 — بدء *</label>
                        <input class="form-control" type="time" name="shift_1_start" id="addS1S" value="12:00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">وردية 1 — انتهاء *</label>
                        <input class="form-control" type="time" name="shift_1_end" id="addS1E" value="16:00" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">وردية 2 — بدء (اختياري)</label>
                        <input class="form-control" type="time" name="shift_2_start" id="addS2S">
                    </div>
                    <div class="form-group">
                        <label class="form-label">وردية 2 — انتهاء</label>
                        <input class="form-control" type="time" name="shift_2_end" id="addS2E">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">وردية 3 — بدء (اختياري)</label>
                        <input class="form-control" type="time" name="shift_3_start" id="addS3S">
                    </div>
                    <div class="form-group">
                        <label class="form-label">وردية 3 — انتهاء</label>
                        <input class="form-control" type="time" name="shift_3_end" id="addS3E">
                    </div>
                </div>

                <div class="form-section">الدوام الإضافي</div>
                <div class="form-row col3">
                    <div class="form-group">
                        <label class="form-label">مسموح</label>
                        <select class="form-control" name="allow_overtime">
                            <option value="1">نعم</option>
                            <option value="0">لا</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">يبدأ بعد (دقيقة)</label>
                        <input class="form-control" type="number" name="overtime_start_after" id="addOTAfter" value="60" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">الحد الأدنى (دقيقة)</label>
                        <input class="form-control" type="number" name="overtime_min_duration" id="addOTMin" value="30" min="0">
                    </div>
                </div>
            </div>
    </div>
    <div class="modal-branch-foot">
        <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">إلغاء</button>
        <button type="submit" class="btn btn-primary">حفظ →</button>
    </div>
    </form>
</div>
</div>

<!-- =================== Modal تعديل فرع =================== -->
<div class="modal-overlay" id="editModal">
    <div class="modal-branch">
        <div class="modal-branch-head">تعديل الفرع</div>
        <form method="POST" id="editBranchForm">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="branch_id" id="editBranchId">
            <div class="modal-branch-body">
                <div class="form-section">بيانات الفرع</div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">اسم الفرع *</label>
                        <input class="form-control" name="name" id="eName" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">نصف قطر النطاق (م)</label>
                        <input class="form-control" type="number" name="geofence_radius" id="eRadius" min="1">
                    </div>
                </div>
                <div class="form-section">
                    الموقع الجغرافي
                    <button type="button" class="btn btn-secondary btn-sm" onclick="useMyLocation('edit')" style="margin-right:auto;font-size:.72rem;padding:3px 10px">📍 موقعي الحالي</button>
                </div>
                <div id="branchMapEdit"></div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">خط العرض</label>
                        <input class="form-control" type="number" step="any" name="latitude" id="eLat" style="direction:ltr" oninput="syncMapFromInputs('edit')">
                    </div>
                    <div class="form-group">
                        <label class="form-label">خط الطول</label>
                        <input class="form-control" type="number" step="any" name="longitude" id="eLon" style="direction:ltr" oninput="syncMapFromInputs('edit')">
                    </div>
                </div>

                <div class="form-section">الورديات</div>
                <div style="background:linear-gradient(135deg,#EFF6FF,#DBEAFE);border:1px solid #93C5FD;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:.8rem;color:#1E40AF;line-height:1.6">
                    تسجيل الحضور متاح قبل بدء الوردية بساعة وحتى نهايتها. الانصراف يتم تلقائياً عند انتهاء الوردية.
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">وردية 1 — بدء *</label>
                        <input class="form-control" type="time" name="shift_1_start" id="eS1S" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">وردية 1 — انتهاء *</label>
                        <input class="form-control" type="time" name="shift_1_end" id="eS1E" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">وردية 2 — بدء (اختياري)</label>
                        <input class="form-control" type="time" name="shift_2_start" id="eS2S">
                    </div>
                    <div class="form-group">
                        <label class="form-label">وردية 2 — انتهاء</label>
                        <input class="form-control" type="time" name="shift_2_end" id="eS2E">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">وردية 3 — بدء (اختياري)</label>
                        <input class="form-control" type="time" name="shift_3_start" id="eS3S">
                    </div>
                    <div class="form-group">
                        <label class="form-label">وردية 3 — انتهاء</label>
                        <input class="form-control" type="time" name="shift_3_end" id="eS3E">
                    </div>
                </div>

                <div class="form-section">الدوام الإضافي</div>
                <div class="form-row col3">
                    <div class="form-group">
                        <label class="form-label">مسموح</label>
                        <select class="form-control" name="allow_overtime" id="eOT">
                            <option value="1">نعم</option>
                            <option value="0">لا</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">يبدأ بعد (دقيقة)</label>
                        <input class="form-control" type="number" name="overtime_start_after" id="eOTAfter" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">الحد الأدنى (دقيقة)</label>
                        <input class="form-control" type="number" name="overtime_min_duration" id="eOTMin" min="0">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">الحالة</label>
                        <select class="form-control" name="is_active" id="eActive">
                            <option value="1">مفعّل</option>
                            <option value="0">معطّل</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-branch-foot">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ →</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById(id).classList.add('show');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('show');
    }

    function openWhatsAppLinks(employees) {
        if (!employees || !employees.length) return;
        if (!confirm('سيتم فتح ' + employees.length + ' نافذة واتساب. هل تريد المتابعة؟')) return;
        const siteUrl = <?= json_encode(SITE_URL) ?>;
        employees.forEach(function(emp, i) {
            const msg = 'مرحباً ' + emp.name + '\n\nرابط تسجيل الحضور:\n' + siteUrl + '/employee/\n\nرمز الدخول (PIN): ' + emp.pin + '\n\nافتح الرابط وأدخل الرمز للتسجيل.';
            const url = 'https://api.whatsapp.com/send/?phone=' + emp.phone + '&text=' + encodeURIComponent(msg) + '&type=phone_number&app_absent=0';
            setTimeout(function() { window.open(url, '_blank'); }, i * 800);
        });
    }

    // خريطة الإضافة
    let addMap, addMarker;

    function openAddModal() {
        openModal('addModal');
        setTimeout(() => {
            if (addMap) { addMap.remove(); addMap = null; addMarker = null; }
            addMap = L.map('branchMapAdd').setView([24.7136, 46.6753], 13);
            var addStreet = L.tileLayer('../api/tile.php?l=street&z={z}&y={y}&x={x}', {
                attribution: '© Esri', maxZoom: 19
            });
            var addSatellite = L.tileLayer('../api/tile.php?l=satellite&z={z}&y={y}&x={x}', {
                attribution: '© Esri', maxZoom: 19
            });
            addSatellite.addTo(addMap);
            L.control.layers({'خريطة': addStreet, 'قمر صناعي': addSatellite}, {}, {position: 'topright'}).addTo(addMap);
            addMap.on('click', function(e) {
                placeMarker('add', e.latlng.lat, e.latlng.lng);
            });
            addMap.invalidateSize();
            // إذا كانت هناك قيم موجودة في الحقول عرضها على الخريطة
            const lat = parseFloat(document.getElementById('addLat').value);
            const lon = parseFloat(document.getElementById('addLon').value);
            if (lat && lon) placeMarker('add', lat, lon, false);
        }, 350);
    }

    // خريطة التعديل
    let editMap, editMarker;

    function openEditModal(b) {
        document.getElementById('editBranchId').value = b.id;
        document.getElementById('eName').value = b.name;
        document.getElementById('eRadius').value = b.geofence_radius;
        document.getElementById('eLat').value = b.latitude;
        document.getElementById('eLon').value = b.longitude;
        // ورديات
        document.getElementById('eS1S').value = b.shift_1_start || '';
        document.getElementById('eS1E').value = b.shift_1_end || '';
        document.getElementById('eS2S').value = b.shift_2_start || '';
        document.getElementById('eS2E').value = b.shift_2_end || '';
        document.getElementById('eS3S').value = b.shift_3_start || '';
        document.getElementById('eS3E').value = b.shift_3_end || '';
        document.getElementById('eOT').value = b.allow_overtime;
        document.getElementById('eOTAfter').value = b.overtime_start_after;
        document.getElementById('eOTMin').value = b.overtime_min_duration;
        document.getElementById('eActive').value = b.is_active;
        openModal('editModal');
        setTimeout(() => {
            const lat = parseFloat(b.latitude) || 24.7136;
            const lon = parseFloat(b.longitude) || 46.6753;
            if (editMap) { editMap.remove(); editMap = null; editMarker = null; }
            editMap = L.map('branchMapEdit').setView([lat, lon], 16);
            var editStreet = L.tileLayer('../api/tile.php?l=street&z={z}&y={y}&x={x}', {
                attribution: '© Esri', maxZoom: 19
            });
            var editSatellite = L.tileLayer('../api/tile.php?l=satellite&z={z}&y={y}&x={x}', {
                attribution: '© Esri', maxZoom: 19
            });
            editSatellite.addTo(editMap);
            L.control.layers({'خريطة': editStreet, 'قمر صناعي': editSatellite}, {}, {position: 'topright'}).addTo(editMap);
            editMap.on('click', function(e) {
                placeMarker('edit', e.latlng.lat, e.latlng.lng);
            });
            editMarker = L.marker([lat, lon]).addTo(editMap);
            editMap.invalidateSize();
        }, 350);
    }

    // وضع أو تحريك الماركر وتحديث حقول الإدخال
    function placeMarker(mode, lat, lon, updateInputs = true) {
        const map = mode === 'add' ? addMap : editMap;
        if (!map) return;
        if (mode === 'add') {
            if (addMarker) addMap.removeLayer(addMarker);
            addMarker = L.marker([lat, lon]).addTo(addMap);
        } else {
            if (editMarker) editMap.removeLayer(editMarker);
            editMarker = L.marker([lat, lon]).addTo(editMap);
        }
        map.setView([lat, lon], map.getZoom() < 14 ? 16 : map.getZoom());
        if (updateInputs) {
            const latId = mode === 'add' ? 'addLat' : 'eLat';
            const lonId = mode === 'add' ? 'addLon' : 'eLon';
            document.getElementById(latId).value = lat.toFixed(8);
            document.getElementById(lonId).value = lon.toFixed(8);
        }
    }

    // مزامنة الخريطة عند تعديل حقول الإدخال يدوياً
    function syncMapFromInputs(mode) {
        const latId = mode === 'add' ? 'addLat' : 'eLat';
        const lonId = mode === 'add' ? 'addLon' : 'eLon';
        const lat = parseFloat(document.getElementById(latId).value);
        const lon = parseFloat(document.getElementById(lonId).value);
        if (!isNaN(lat) && !isNaN(lon) && lat >= -90 && lat <= 90 && lon >= -180 && lon <= 180) {
            placeMarker(mode, lat, lon, false);
        }
    }

    // استخدام الموقع الحالي (GPS)
    function useMyLocation(mode) {
        if (!navigator.geolocation) {
            alert('المتصفح لا يدعم تحديد الموقع');
            return;
        }
        navigator.geolocation.getCurrentPosition(
            function(pos) {
                placeMarker(mode, pos.coords.latitude, pos.coords.longitude);
            },
            function(err) {
                alert('تعذّر تحديد الموقع: ' + err.message);
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    }

    // إغلاق modal عند الضغط خارجه
    document.querySelectorAll('.modal-overlay').forEach(o => {
        o.addEventListener('click', e => {
            if (e.target === o) o.classList.remove('show');
        });
    });

</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

</div>
</div>
</body>

</html>