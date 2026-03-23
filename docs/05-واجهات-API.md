# 🔌 توثيق واجهات API — جميع نقاط الوصول البرمجية

> هذا المستند يوثّق كل نقطة API مع الطلب والاستجابة ورموز الأخطاء.

---

## المعلومات الأساسية

```
Base URL:       https://mycorner.site/api
Content-Type:   application/json
Accept:         application/json
Authentication: Token-based (employee) / Session-based (admin)
```

---

## 1. المصادقة (Authentication)

### 1.1 `POST /api/auth-pin` — الدخول برمز PIN

**Rate Limit:** 10 طلبات/دقيقة

**الطلب:**
```json
{
    "pin": "3847"
}
```

| المعامل | النوع | مطلوب | الوصف |
|---------|-------|-------|-------|
| `pin` | string | ✅ | رمز PIN (4 أرقام بالضبط) |

**الاستجابة الناجحة (200):**
```json
{
    "success": true,
    "employee": {
        "id": 1,
        "name": "أحمد محمد",
        "job_title": "محاسب",
        "branch": "الفرع الرئيسي",
        "token": "a7b3c9d1e5f2..."
    }
}
```

**الاستجابة الفاشلة (403):**
```json
{
    "success": false,
    "message": "رقم PIN غير صالح أو الموظف غير مفعّل"
}
```

**تغييرات قاعدة البيانات:** لا شيء

---

### 1.2 `POST /api/auth-device` — الدخول ببصمة الجهاز

**Rate Limit:** 20 طلب/دقيقة

**الطلب:**
```json
{
    "fingerprint": "x7k2m9n4p8..."
}
```

| المعامل | النوع | مطلوب | الوصف |
|---------|-------|-------|-------|
| `fingerprint` | string | ✅ | بصمة الجهاز (حتى 64 حرف) |

**الاستجابة الناجحة (200):**
```json
{
    "success": true,
    "employee": {
        "id": 1,
        "name": "أحمد محمد",
        "job_title": "محاسب",
        "branch": "الفرع الرئيسي",
        "token": "a7b3c9d1e5f2..."
    }
}
```

**الاستجابة الفاشلة (200):**
```json
{
    "success": false,
    "message": "الجهاز غير مسجل",
    "registered": false
}
```

**تغييرات قاعدة البيانات:**
- `known_devices.usage_count` += 1
- `known_devices.last_used_at` → الوقت الحالي

---

### 1.3 `POST /api/verify-device` — التحقق من الجهاز

**Rate Limit:** 30 طلب/دقيقة

**الطلب:**
```json
{
    "token": "a7b3c9d1e5f2...",
    "fingerprint": "x7k2m9n4p8..."
}
```

**السيناريوهات:**

| الحالة | `device_bind_mode` | `device_fingerprint` | البصمة المرسلة | النتيجة | تغييرات DB |
|--------|-------------------|---------------------|---------------|---------|-----------|
| وضع حر | 0 | أي قيمة | أي قيمة | ✅ مسموح | لا شيء |
| أول ربط | 1 أو 2 | NULL | أي قيمة | ✅ تم الربط | `employees.device_fingerprint` + `known_devices` جديد |
| نفس الجهاز | 1 أو 2 | "x7k..." | "x7k..." | ✅ مطابق | `known_devices.last_used_at` |
| جهاز مختلف + صارم | 1 | "x7k..." | "y8n..." | ❌ مرفوض | `tampering_cases` جديد |
| جهاز مختلف + مراقبة | 2 | "x7k..." | "y8n..." | ✅ مسموح + تنبيه | `tampering_cases` جديد |

---

### 1.4 `GET /api/get-employee` — جلب بيانات الموظف

**Rate Limit:** 30 طلب/دقيقة

**الطلب:**
```
GET /api/get-employee?token=a7b3c9d1e5f2...
```

| المعامل | النوع | مطلوب | الوصف |
|---------|-------|-------|-------|
| `token` | query string | ✅ | Token الموظف (64 حرف) |

**الاستجابة الناجحة (200):**
```json
{
    "success": true,
    "employee": {
        "id": 1,
        "name": "أحمد محمد",
        "job_title": "محاسب",
        "branch": "الفرع الرئيسي"
    },
    "last_record": {
        "type": "in",
        "timestamp": "2026-03-15 08:15:30"
    }
}
```

**`last_record`** يكون `null` إذا لم يُسجّل الموظف أي حضور اليوم.

---

## 2. الحضور والانصراف

### 2.1 `POST /api/check-in` — تسجيل الحضور

**Rate Limit:** 30 طلب/دقيقة

**الطلب:**
```json
{
    "token": "a7b3c9d1e5f2...",
    "latitude": 24.72350000,
    "longitude": 46.63510000,
    "accuracy": 15.00
}
```

| المعامل | النوع | مطلوب | الوصف |
|---------|-------|-------|-------|
| `token` | string | ✅ | Token الموظف (64 حرف بالضبط) |
| `latitude` | numeric | ✅ | خط عرض الموظف |
| `longitude` | numeric | ✅ | خط طول الموظف |
| `accuracy` | numeric | ❌ | دقة GPS بالمتر |

**التحققات بالترتيب:**
1. ✅ Token صالح ومرتبط بموظف نشط
2. ✅ الوقت الحالي ضمن نافذة الحضور (`check_in_start_time` - `check_in_end_time`)
3. ✅ الموظف داخل السياج الجغرافي (المسافة ≤ `geofence_radius`)
4. ✅ لم يُسجّل حضور من نفس النوع خلال 5 دقائق

**الاستجابة الناجحة (200):**
```json
{
    "success": true,
    "message": "تم تسجيل الدخول بنجاح",
    "late_minutes": 15,
    "employee_name": "أحمد محمد",
    "timestamp": "2026-03-15 08:15:30",
    "distance": 18
}
```

**أمثلة على الفشل:**

خارج النطاق:
```json
{
    "success": false,
    "message": "أنت خارج نطاق العمل! المسافة: 1200 متر (الحد المسموح: 500 متر)",
    "distance": 1200
}
```

خارج النافذة الزمنية:
```json
{
    "success": false,
    "message": "وقت تسجيل الدخول المسموح به: 07:00 - 10:00. الوقت الحالي: 11:30"
}
```

مسجّل مسبقاً:
```json
{
    "success": false,
    "message": "تم التسجيل مسبقاً خلال آخر 5 دقائق"
}
```

**تغييرات قاعدة البيانات:** `attendances` → سجل جديد type='in'

---

### 2.2 `POST /api/check-out` — تسجيل الانصراف

**مطابق لـ check-in** في البنية والمعاملات.

**الفرق:**
- يتحقق من نافذة الانصراف (`check_out_start_time` - `check_out_end_time`)
- `late_minutes` دائماً = 0
- type = 'out'

**تغييرات قاعدة البيانات:** `attendances` → سجل جديد type='out'

---

## 3. العمل الإضافي

### 3.1 `POST /api/overtime` — بدء العمل الإضافي

**Rate Limit:** 20 طلب/دقيقة

**الطلب:** (نفس بنية check-in)
```json
{
    "token": "a7b3c9d1e5f2...",
    "latitude": 24.72350000,
    "longitude": 46.63510000,
    "accuracy": 15.00
}
```

**التحققات:**
1. ✅ Token صالح
2. ✅ العمل الإضافي مسموح في إعدادات الفرع (`allow_overtime = true`)
3. ✅ يوجد سجل انصراف (type='out') اليوم
4. ✅ الموظف داخل السياج الجغرافي

**الاستجابة الناجحة:**
```json
{
    "success": true,
    "message": "تم بدء الدوام الإضافي",
    "late_minutes": 0,
    "employee_name": "أحمد محمد",
    "timestamp": "2026-03-15 17:30:00"
}
```

**فشل — لم ينصرف بعد:**
```json
{
    "success": false,
    "message": "يجب تسجيل الانصراف أولاً قبل بدء الدوام الإضافي"
}
```

**فشل — الإضافي غير مسموح:**
```json
{
    "success": false,
    "message": "الدوام الإضافي غير مسموح لهذا الفرع"
}
```

**تغييرات DB:** `attendances` → سجل جديد type='overtime-start'

---

### 3.2 `POST /api/overtime-end` — إنهاء العمل الإضافي

**الطلب:** (نفس البنية)

**التحققات:**
1. ✅ Token صالح
2. ✅ يوجد سجل `overtime-start` اليوم
3. ✅ المدة ≥ `overtime_min_duration` (افتراضي 30 دقيقة)

**الاستجابة الناجحة:**
```json
{
    "success": true,
    "message": "تم إنهاء الدوام الإضافي",
    "late_minutes": 0,
    "employee_name": "أحمد محمد",
    "timestamp": "2026-03-15 18:15:00",
    "duration": 45
}
```

**فشل — المدة قصيرة:**
```json
{
    "success": false,
    "message": "الحد الأدنى للدوام الإضافي 30 دقيقة. المدة الحالية: 15 دقيقة"
}
```

**تغييرات DB:** `attendances` → سجل جديد type='overtime-end'

---

## 4. البلاغات والإجازات

### 4.1 `POST /api/submit-report` — تقديم بلاغ سري

**Rate Limit:** 10 طلبات/ساعة

**الطلب:** (multipart/form-data)

| المعامل | النوع | مطلوب | الوصف |
|---------|-------|-------|-------|
| `employee_id` | integer | ✅ | رقم الموظف |
| `report_type` | string | ❌ | نوع البلاغ (violation/complaint/suggestion/harassment/safety/other) |
| `report_text` | string | ❌ | نص البلاغ |
| `images[]` | file(s) | ❌ | صور مرفقة (متعددة) |

**الاستجابة الناجحة:**
```json
{
    "success": true,
    "message": "تم إرسال البلاغ بنجاح"
}
```

**تغييرات DB:** `secret_reports` → سجل جديد

---

### 4.2 `POST /api/leave-add` — إضافة إجازة

**المصادقة:** admin.auth (يتطلب جلسة مدير)

**الطلب:**
```json
{
    "employee_id": 1,
    "leave_type": "annual",
    "start_date": "2026-03-20",
    "end_date": "2026-03-25",
    "reason": "إجازة عائلية"
}
```

**التحققات:**
- لا يوجد تداخل مع إجازة غير مرفوضة لنفس الموظف

**تغييرات DB:** `leaves` → سجل جديد status='pending'

---

## 5. البيانات اللحظية والتقارير (Admin فقط)

### 5.1 `GET /api/realtime-dashboard` — بيانات لوحة القيادة

**المصادقة:** admin.auth

**الاستجابة:**
```json
{
    "stats": {
        "checked_in": 35,
        "checked_out": 12,
        "total_employees": 50
    },
    "recent": [
        {
            "employee_name": "أحمد محمد",
            "branch_name": "الفرع الرئيسي",
            "type": "in",
            "timestamp": "08:15:30"
        }
    ],
    "absent": [
        {
            "name": "خالد عمر",
            "job_title": "مهندس",
            "branch": "الفرع الرئيسي"
        }
    ],
    "branches": [...]
}
```

---

### 5.2 `GET /api/realtime-attendance` — سجلات الحضور

**المصادقة:** admin.auth

**معاملات الاستعلام:**
```
?date_from=2026-03-01
&date_to=2026-03-15
&branch_id=1
&type=in
&employee_id=1
&page=1
```

---

### 5.3 `GET /api/export` — تصدير البيانات

**المصادقة:** admin.auth

**معاملات الاستعلام:**
```
?format=csv              # csv أو json
&date_from=2026-03-01
&date_to=2026-03-15
&branch_id=1
```

**CSV Response:** يُرسل ملف `export.csv` مع UTF-8 BOM

**JSON Response:**
```json
{
    "meta": {
        "total": 250,
        "exported_at": "2026-03-15T14:30:00"
    },
    "records": [...]
}
```

---

## 6. واتساب

### 6.1 `POST /api/whatsapp` — توليد رابط واتساب

**المصادقة:** admin.auth

**الطلب:**
```json
{
    "employee_id": 1
}
```

**الاستجابة:**
```json
{
    "success": true,
    "link": "https://wa.me/966501234567?text=...",
    "employee_name": "أحمد محمد"
}
```

---

### 6.2 `POST /api/send-all-links` — توليد روابط لجميع الموظفين

**المصادقة:** admin.auth

**الاستجابة:** مصفوفة روابط واتساب لكل موظف نشط لديه رقم هاتف

---

### 6.3 `POST /api/regenerate-tokens` — إعادة توليد كل الرموز

**المصادقة:** admin.auth

**⚠️ تحذير:** يُبطل جميع روابط الموظفين الحالية!

**تغييرات DB:** `employees.unique_token` → قيمة جديدة لكل موظف

---

## 7. فحص النظام

### 7.1 `GET /api/health` — فحص صحة النظام

**بدون مصادقة**

**الاستجابة:**
```json
{
    "status": "ok",
    "database": "connected",
    "php_version": "8.3.27",
    "laravel_version": "11.x",
    "server_time": "2026-03-15T14:30:00+03:00"
}
```

---

## ملخص رموز الاستجابة

| الرمز | المعنى | متى يُستخدم |
|-------|--------|-----------|
| `200` | نجاح | كل الطلبات الناجحة (تحقق `success` في body) |
| `201` | تم الإنشاء | إنشاء موارد جديدة (Public API) |
| `302` | إعادة توجيه | تسجيل دخول ناجح (web) |
| `400` | طلب غير صالح | معاملات مفقودة |
| `401` | غير مصادق | Token/Sanctum غير صالح |
| `403` | ممنوع | token/pin غير صالح أو جهاز غير مصرح |
| `404` | غير موجود | مسار غير موجود |
| `419` | CSRF Token منتهي | جلسة الويب انتهت |
| `422` | خطأ تحقق | فشل validation (Laravel auto) |
| `429` | كثرة الطلبات | تجاوز حد Rate Limit |
| `500` | خطأ خادم | خطأ برمجي داخلي |

---

## 8. Public API v1 — واجهة خارجية بمصادقة Sanctum

> هذه الواجهة مُعدّة للتكامل مع أنظمة خارجية (تطبيقات موبايل، أنظمة رواتب، أدوات تحليل...)

### المعلومات الأساسية

```
Base URL:       https://mycorner.site/api/v1
Authentication: Bearer Token (Laravel Sanctum)
Content-Type:   application/json
```

### 8.1 `POST /api/tokens/create` — إنشاء رمز API

**Rate Limit:** 5 طلبات/دقيقة

**الطلب:**
```json
{
    "username": "admin",
    "password": "Admin@1234",
    "token_name": "Mobile App",
    "abilities": ["read", "write"]
}
```

**الاستجابة الناجحة (200):**
```json
{
    "token": "1|abc123...",
    "type": "Bearer",
    "abilities": ["read", "write"]
}
```

**الاستخدام:**
```
Authorization: Bearer 1|abc123...
```

### 8.2 `DELETE /api/tokens/revoke` — إلغاء رمز API

**المصادقة:** sanctum

**تأثير:** يحذف الرمز الحالي نهائياً.

---

### 8.3 `GET /api/v1/attendance` — سجلات الحضور

**المصادقة:** sanctum

**معاملات الاستعلام:**

| المعامل | النوع | وصف |
|---------|-------|-----|
| `date` | date | تاريخ محدد |
| `from` | date | من تاريخ |
| `to` | date | إلى تاريخ |
| `employee_id` | int | تصفية بموظف |
| `type` | string | in/out/overtime-start/overtime-end |

**الاستجابة:** Cursor pagination (100 سجل/صفحة) مع بيانات الموظف.

---

### 8.4 `GET /api/v1/employees` — قائمة الموظفين

**معاملات الاستعلام:**

| المعامل | النوع | وصف |
|---------|-------|-----|
| `branch_id` | int | تصفية بفرع |
| `active` | boolean | نشط/غير نشط |
| `search` | string | بحث بالاسم أو المسمى الوظيفي |

**الاستجابة:** Cursor pagination (50 سجل/صفحة) مع بيانات الفرع.

### 8.5 `GET /api/v1/employees/{id}` — تفاصيل موظف

**الاستجابة:** بيانات الموظف + سجلات حضور اليوم.

### 8.6 `GET /api/v1/branches` — قائمة الفروع

**الاستجابة:** الفروع النشطة مع عدد الموظفين (`employees_count`).

### 8.7 إدارة الإجازات عبر API

```
GET    /api/v1/leaves              — قائمة الإجازات (فلتر: status, employee_id)
POST   /api/v1/leaves              — إنشاء إجازة جديدة
POST   /api/v1/leaves/{id}/approve — الموافقة على إجازة
POST   /api/v1/leaves/{id}/reject  — رفض إجازة
```

**إنشاء إجازة:**
```json
{
    "employee_id": 1,
    "leave_type": "annual",
    "start_date": "2026-04-01",
    "end_date": "2026-04-05",
    "reason": "إجازة عائلية"
}
```

**تحقق:** يرفض إذا وُجد تداخل مع إجازة موجودة (غير مرفوضة).

---

## 9. تصدير التقويم (iCalendar)

> تصدير بصيغة `.ics` لاستيراده في Google Calendar أو Outlook

### 9.1 `GET /api/calendar/leaves` — تصدير الإجازات

**المصادقة:** admin.auth

**معاملات اختيارية:**
```
?employee_id=1    (لموظف محدد)
```

**الاستجابة:** ملف `text/calendar; charset=utf-8`

```ics
BEGIN:VCALENDAR
VERSION:2.0
PRODID://Sarh Attendance//Leaves//AR
BEGIN:VEVENT
UID:leave-1@sarh.io
DTSTART:20260401
DTEND:20260406
SUMMARY:إجازة سنوية - أحمد محمد
DESCRIPTION:إجازة عائلية
END:VEVENT
END:VCALENDAR
```

### 9.2 `GET /api/calendar/schedule` — تصدير جدول الدوام

**المصادقة:** admin.auth

**معاملات مطلوبة:**
```
?employee_id=1    (مطلوب)
```

**السلوك:**
- يُنشئ أحداث لـ 30 يوماً قادماً (يتخطى الجمعة والسبت)
- يستخدم أوقات الدوام المرنة للموظف، أو أوقات فرعه
- Timezone: `Asia/Riyadh`

---

## 10. الإشعارات

### 10.1 `GET /api/notifications` — جلب الإشعارات الأخيرة

**المصادقة:** admin.auth

**الاستجابة:** قائمة آخر الإشعارات غير المقروءة + عددها.

---

## 11. أحداث Webhooks — مرجع

> يُرسل النظام تلقائياً POST requests لأي Webhook مُسجّل عند حدوث هذه الأحداث

### الأحداث المدعومة

| الحدث | المعنى | متى يُطلق |
|-------|--------|-----------|
| `attendance.checkin` | تسجيل حضور | عند تسجيل الموظف حضوره |
| `attendance.checkout` | تسجيل انصراف | عند تسجيل الانصراف |
| `attendance.overtime` | عمل إضافي | بدء/إنهاء عمل إضافي |
| `leave.created` | طلب إجازة | عند إنشاء طلب إجازة جديد |
| `leave.approved` | موافقة إجازة | عند اعتماد الإجازة |
| `leave.rejected` | رفض إجازة | عند رفض الإجازة |
| `report.submitted` | بلاغ سري | عند تقديم بلاغ |
| `tampering.detected` | كشف تلاعب | عند اكتشاف تلاعب بالجهاز |
| `employee.created` | إضافة موظف | عند إنشاء موظف جديد |
| `employee.deleted` | حذف موظف | عند حذف موظف |

### بنية الطلب المُرسل

```json
{
    "event": "attendance.checkin",
    "timestamp": "2026-03-15T08:15:30Z",
    "data": {
        "employee_id": 1,
        "employee_name": "أحمد محمد",
        "branch": "الفرع الرئيسي",
        "type": "in",
        "late_minutes": 15
    }
}
```

### رؤوس الطلب

| الرأس | القيمة | الوصف |
|-------|--------|-------|
| `Content-Type` | `application/json` | نوع المحتوى |
| `X-Webhook-Event` | اسم الحدث | الحدث المُطلق |
| `X-Webhook-Signature` | HMAC-SHA256 | توقيع رقمي للتحقق |

### التوقيع الرقمي (HMAC-SHA256)

```
Signature = HMAC-SHA256(request_body, webhook_secret)
```

**للتحقق من جانب المُستقبِل:**
```php
$expected = hash_hmac('sha256', $requestBody, $secret);
$received = $request->header('X-Webhook-Signature');
if (!hash_equals($expected, $received)) {
    return response('Invalid signature', 403);
}
```

### سياسة إعادة المحاولة

- **Timeout:** 10 ثوانٍ لكل طلب
- **عند الفشل:** يزداد `failure_count` بمقدار 1
- **تعطيل تلقائي:** بعد **10 إخفاقات متتالية** يُعطّل الـ Webhook
- **عند النجاح:** يصفّر `failure_count`
