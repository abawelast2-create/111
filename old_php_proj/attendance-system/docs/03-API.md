# نقاط نهاية API — التوثيق الكامل

جميع نقاط النهاية تستخدم:
- **الطريقة:** POST فقط
- **نوع الجسم:** `application/json`
- **الترميز:** UTF-8
- **المسار الجذر:** `/attendance-system/api/`

---

## 1. تسجيل الحضور

**`POST /api/check-in.php`**

### الطلب

```json
{
  "token": "56f3a733e65444450bc8...",
  "latitude": 24.572307,
  "longitude": 46.602552,
  "accuracy": 12.5
}
```

| الحقل | النوع | مطلوب | الوصف |
|-------|-------|-------|-------|
| `token` | string | ✅ | الرمز الفريد للموظف |
| `latitude` | float | ✅ | خط العرض من GPS |
| `longitude` | float | ✅ | خط الطول من GPS |
| `accuracy` | float | لا | دقة GPS بالمتر |

### الاستجابة — نجاح

```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح",
  "employee_name": "منذر محمود",
  "timestamp": "2026-03-02 08:30:00",
  "distance": 45
}
```

### الاستجابة — فشل (خارج النطاق)

```json
{
  "success": false,
  "message": "أنت خارج نطاق العمل! المسافة: 350 متر (الحد المسموح: 200 متر)",
  "distance": 350
}
```

### التحقق المنفّذ بالترتيب

1. الطريقة POST
2. صحة JSON
3. صحة Token + الموظف مفعّل
4. الوقت ضمن نافذة الدخول المسموحة
5. الموقع ضمن النطاق الجغرافي
6. عدم التسجيل المكرر (خلال 5 دقائق)

---

## 2. تسجيل الانصراف

**`POST /api/check-out.php`**

### الطلب

```json
{
  "token": "56f3a733e65444450bc8...",
  "latitude": 24.572307,
  "longitude": 46.602552,
  "accuracy": 20.0
}
```

### الاستجابة — نجاح

```json
{
  "success": true,
  "message": "تم تسجيل الانصراف بنجاح",
  "employee_name": "منذر محمود",
  "timestamp": "2026-03-02 16:05:00",
  "distance": 38
}
```

### التحقق المنفّذ

1. صحة Token
2. **وجود تسجيل دخول اليوم** (شرط إلزامي)
3. الموقع ضمن النطاق الجغرافي
4. عدم التسجيل المكرر

---

## 3. تسجيل الدوام الإضافي

**`POST /api/ot.php`**

### الطلب

```json
{
  "token": "56f3a733e65444450bc8...",
  "latitude": 24.572307,
  "longitude": 46.602552,
  "accuracy": 15.0
}
```

### الاستجابة — نجاح

```json
{
  "success": true,
  "message": "تم تسجيل الدوام الإضافي بنجاح"
}
```

### التحقق المنفّذ

1. الدوام الإضافي **مفعّل** في الإعدادات
2. صحة Token
3. الموقع ضمن النطاق الجغرافي
4. **وجود تسجيل انصراف اليوم** (شرط إلزامي)
5. عدم تسجيل دوام إضافي سابق اليوم

---

## 4. التحقق من بصمة الجهاز

**`POST /api/verify-device.php`**

### الطلب

```json
{
  "token": "56f3a733e65444450bc8...",
  "fingerprint": "a3f9c2d1e8b4..."
}
```

| الحقل | الوصف |
|-------|-------|
| `token` | الرمز الفريد للموظف |
| `fingerprint` | SHA-256 لبصمة الجهاز (64 حرف hex) |

### أوضاع ربط الجهاز (`device_bind_mode`)

| القيمة | الوضع | السلوك |
|--------|-------|--------|
| 0 | حر | الرابط يعمل من أي جهاز بدون قيود |
| 1 | صارم | الرابط مربوط بجهاز واحد — أي جهاز آخر يُحظر |
| 2 | مراقبة صامتة | يسمح بالدخول من أي جهاز لكن يُسجّل التلاعب بصمت |

### تتبع الأجهزة (`known_devices`)

عند كل دخول، يتم تسجيل/تحديث الجهاز في جدول `known_devices`:
- إذا كان الجهاز موجوداً: يزيد `usage_count` ويُحدّث `last_used_at`
- إذا كان جديداً: يُنشأ سطر جديد بـ `usage_count = 1`
- **مالك الجهاز:** الموظف صاحب أعلى `usage_count` لنفس البصمة

### الاستجابات المحتملة

**الوضع الحر (`device_bind_mode = 0`) — أول دخول:**
```json
{
  "success": true,
  "first_time": true,
  "auto_bound": false
}
```
> يدخل بدون ربط. لربط الجهاز، يُفعّل المشرف وضع الربط يدوياً.

**أول دخول (الربط مفعّل يدوياً من المشرف):**
```json
{
  "success": true,
  "first_time": true,
  "auto_bound": true
}
```
> تم ربط الجهاز وتحويل `device_bind_mode` إلى الوضع المختار.

**جهاز مطابق (أي وضع):**
```json
{
  "success": true,
  "first_time": false
}
```

**جهاز مختلف — الوضع الصارم (`device_bind_mode = 1`):**
```json
{
  "success": false,
  "locked": true,
  "message": "هذا الرابط مرتبط بجهاز آخر. تواصل مع المشرف لإعادة تعيين الجهاز."
}
```
> يُحظر الدخول ويُعرض شاشة قفل.

**جهاز مختلف — المراقبة الصامتة (`device_bind_mode = 2`):**
```json
{
  "success": true,
  "first_time": false
}
```
> يُسمح بالدخول لكن يُسجّل حالة تلاعب `different_device` في جدول `tampering_cases` مع تفاصيل البصمة وIP ومالك الجهاز.

---

## 5. إرسال جميع الروابط عبر واتساب

**`POST /api/send-all-links.php`**

> يتطلب جلسة مدير نشطة + CSRF Token

### الطلب (FormData)

| الحقل | النوع | مطلوب | الوصف |
|-------|-------|-------|-------|
| `csrf_token` | string | ✅ | رمز CSRF |
| `phone` | string | لا | رقم الهاتف (افتراضي: 966578448146) |

### الاستجابة — نجاح

```json
{
  "success": true,
  "wa_url": "https://wa.me/966578448146?text=...",
  "count": 40,
  "message": "تم تجهيز 40 رابط للإرسال"
}
```

### الوصف

يجمع جميع روابط الموظفين النشطين مرتبة حسب الفرع في رسالة واحدة،
ويُعيد رابط wa.me جاهز للفتح في واتساب.

---

## رموز HTTP المستخدمة

| الرمز | المعنى | الحالة |
|-------|--------|--------|
| 200 | ناجح | نجاح أو فشل بيزنس لوجيك |
| 400 | بيانات ناقصة / غير صالحة | بيانات الطلب معطوبة |
| 403 | غير مصرح | token خاطئ أو موظف معطّل |
| 405 | طريقة غير مسموحة | ليس POST |
| 500 | خطأ في الخادم | خطأ DB أو PHP |

---

## حساب بصمة الجهاز (JavaScript)

```javascript
async function getFingerprint() {
  const parts = [
    navigator.userAgent,
    screen.width + 'x' + screen.height + 'x' + screen.colorDepth,
    Intl.DateTimeFormat().resolvedOptions().timeZone,
    navigator.language,
    navigator.platform || ''
  ];
  // Canvas fingerprint — بصمة رسومية فريدة
  const c = document.createElement('canvas');
  const x = c.getContext('2d');
  x.font = '14px Arial'; x.fillText('fp', 2, 2);
  parts.push(c.toDataURL().slice(-50));

  const raw = parts.join('|');
  const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(raw));
  return Array.from(new Uint8Array(buf))
    .map(b => b.toString(16).padStart(2, '0')).join('');
}
```
