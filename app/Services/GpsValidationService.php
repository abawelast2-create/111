<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\LocationLog;
use App\Models\TamperingCase;

class GpsValidationService
{
    // دقة GPS: رفض القيم المنخفضة جداً أو المرتفعة جداً
    const MIN_ACCURACY = 10;    // متر
    const MAX_ACCURACY = 1000;  // متر

    // الحد الأقصى للسرعة المعقولة (كم/ساعة) - أعلى من سرعة سيارة عادية يعني تزوير
    const MAX_REASONABLE_SPEED = 200;

    // الحد الأدنى لدرجة الثقة للسماح بالتسجيل
    const MIN_TRUST_SCORE = 30;

    /**
     * التحقق الشامل من صحة الموقع الجغرافي
     */
    public static function validate(
        Employee $employee,
        float $latitude,
        float $longitude,
        ?float $accuracy = null,
        ?array $wifiNetworks = null,
        ?string $ipAddress = null
    ): array {
        $score = 100;
        $flags = [];
        $isSuspicious = false;

        // 1. فحص دقة الموقع
        if ($accuracy !== null) {
            if ($accuracy < self::MIN_ACCURACY) {
                $score -= 25;
                $flags[] = 'دقة GPS منخفضة بشكل مريب (< ' . self::MIN_ACCURACY . 'm)';
            } elseif ($accuracy > self::MAX_ACCURACY) {
                $score -= 20;
                $flags[] = 'دقة GPS مرتفعة جداً (> ' . self::MAX_ACCURACY . 'm)';
            }
        }

        // 2. فحص السرعة غير المعقولة (مقارنة مع آخر موقع)
        $speedCheck = self::checkSpeed($employee->id, $latitude, $longitude);
        if ($speedCheck['suspicious']) {
            $score -= 30;
            $flags[] = $speedCheck['reason'];
        }

        // 3. مقارنة عنوان IP مع موقع الفرع
        if ($ipAddress && $employee->branch_id) {
            $ipCheck = self::checkIpConsistency($ipAddress, $employee->branch_id);
            if (!$ipCheck['consistent']) {
                $score -= 15;
                $flags[] = $ipCheck['reason'];
            }
        }

        // 4. التحقق من شبكات Wi-Fi
        if ($wifiNetworks && $employee->branch_id) {
            $wifiCheck = self::checkWifiNetworks($wifiNetworks, $employee->branch_id);
            if (!$wifiCheck['matched']) {
                $score -= 10;
                $flags[] = $wifiCheck['reason'];
            } else {
                $score += 10; // مكافأة لتطابق Wi-Fi
            }
        }

        // 5. مقارنة مع نطاق ثقة الموظف
        if ($employee->trust_radius && $employee->avg_latitude && $employee->avg_longitude) {
            $distFromAvg = GeofenceService::calculateDistance(
                $latitude, $longitude,
                (float) $employee->avg_latitude, (float) $employee->avg_longitude
            );
            if ($distFromAvg > $employee->trust_radius * 2) {
                $score -= 15;
                $flags[] = "الموقع بعيد عن النطاق المعتاد ({$distFromAvg}m)";
            }
        }

        $score = max(0, min(100, $score));
        $isSuspicious = $score < self::MIN_TRUST_SCORE;

        // تسجيل الموقع
        LocationLog::create([
            'employee_id'      => $employee->id,
            'latitude'         => $latitude,
            'longitude'        => $longitude,
            'accuracy'         => $accuracy,
            'speed'            => $speedCheck['speed'] ?? null,
            'ip_address'       => $ipAddress,
            'is_suspicious'    => $isSuspicious,
            'suspicion_reason' => $isSuspicious ? implode(' | ', $flags) : null,
            'recorded_at'      => now(),
        ]);

        // إنشاء حالة تلاعب إذا كانت مريبة جداً
        if ($isSuspicious) {
            TamperingCase::create([
                'employee_id'     => $employee->id,
                'case_type'       => 'gps_spoofing',
                'description'     => 'اشتباه في تزوير الموقع: ' . implode(', ', $flags),
                'attendance_date' => today()->toDateString(),
                'severity'        => $score < 15 ? 'high' : 'medium',
                'details_json'    => [
                    'latitude'     => $latitude,
                    'longitude'    => $longitude,
                    'accuracy'     => $accuracy,
                    'score'        => $score,
                    'flags'        => $flags,
                    'ip'           => $ipAddress,
                    'wifi'         => $wifiNetworks,
                ],
            ]);
        }

        // تحديث متوسط موقع الموظف (إذا كان الموقع موثوقاً)
        if ($score >= 70) {
            self::updateEmployeeAvgLocation($employee, $latitude, $longitude);
        }

        return [
            'score'      => $score,
            'suspicious' => $isSuspicious,
            'flags'      => $flags,
            'allowed'    => !$isSuspicious,
            'message'    => $isSuspicious
                ? 'تم رصد نشاط مشبوه في الموقع الجغرافي. درجة الثقة: ' . $score
                : 'تم التحقق من الموقع بنجاح. درجة الثقة: ' . $score,
        ];
    }

    /**
     * فحص السرعة بين آخر موقعين
     */
    private static function checkSpeed(int $employeeId, float $lat, float $lon): array
    {
        $lastLog = LocationLog::where('employee_id', $employeeId)
            ->orderBy('recorded_at', 'desc')
            ->first();

        if (!$lastLog) {
            return ['suspicious' => false, 'speed' => null];
        }

        $distance = GeofenceService::calculateDistance(
            (float) $lastLog->latitude, (float) $lastLog->longitude, $lat, $lon
        );

        $timeDiffSeconds = now()->diffInSeconds($lastLog->recorded_at);
        if ($timeDiffSeconds < 1) $timeDiffSeconds = 1;

        $speedKmh = ($distance / 1000) / ($timeDiffSeconds / 3600);

        if ($speedKmh > self::MAX_REASONABLE_SPEED && $distance > 100) {
            return [
                'suspicious' => true,
                'speed'      => round($speedKmh, 1),
                'reason'     => "سرعة تنقل غير معقولة: {$speedKmh} كم/ساعة خلال {$timeDiffSeconds} ثوانٍ",
            ];
        }

        return ['suspicious' => false, 'speed' => round($speedKmh, 1)];
    }

    /**
     * فحص توافق عنوان IP مع منطقة الفرع
     */
    private static function checkIpConsistency(string $ip, int $branchId): array
    {
        $branch = Branch::find($branchId);
        if (!$branch || !$branch->allowed_ip_ranges) {
            return ['consistent' => true, 'reason' => ''];
        }

        $ranges = array_map('trim', explode(',', $branch->allowed_ip_ranges));
        foreach ($ranges as $range) {
            if (self::ipInRange($ip, $range)) {
                return ['consistent' => true, 'reason' => ''];
            }
        }

        return [
            'consistent' => false,
            'reason'     => "عنوان IP ({$ip}) لا يتوافق مع نطاقات الفرع المسموحة",
        ];
    }

    /**
     * فحص شبكات Wi-Fi القريبة
     */
    private static function checkWifiNetworks(array $networks, int $branchId): array
    {
        $branch = Branch::find($branchId);
        if (!$branch || !$branch->wifi_ssids) {
            return ['matched' => true, 'reason' => ''];
        }

        $allowed = array_map('trim', explode(',', $branch->wifi_ssids));
        $networkNames = array_column($networks, 'ssid');

        $matched = array_intersect($allowed, $networkNames);
        if (empty($matched)) {
            return [
                'matched' => false,
                'reason'  => 'لم يتم رصد شبكات Wi-Fi الخاصة بالفرع',
            ];
        }

        return ['matched' => true, 'reason' => ''];
    }

    /**
     * تحديث متوسط موقع الموظف
     */
    private static function updateEmployeeAvgLocation(Employee $employee, float $lat, float $lon): void
    {
        if (!$employee->avg_latitude) {
            $employee->update([
                'avg_latitude'  => $lat,
                'avg_longitude' => $lon,
            ]);
            return;
        }

        // المتوسط المتحرك البسيط
        $alpha = 0.1;
        $employee->update([
            'avg_latitude'  => $employee->avg_latitude * (1 - $alpha) + $lat * $alpha,
            'avg_longitude' => $employee->avg_longitude * (1 - $alpha) + $lon * $alpha,
        ]);
    }

    /**
     * فحص ما إذا كان IP ضمن نطاق CIDR
     */
    private static function ipInRange(string $ip, string $range): bool
    {
        if (!str_contains($range, '/')) {
            return $ip === $range;
        }

        [$subnet, $bits] = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        if ($ip === false || $subnet === false) return false;

        $mask = -1 << (32 - (int) $bits);
        return ($ip & $mask) === ($subnet & $mask);
    }
}
