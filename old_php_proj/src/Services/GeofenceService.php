<?php
// =============================================================
// src/Services/GeofenceService.php - خدمة نطاق الموقع الجغرافي
// =============================================================

namespace App\Services;

use App\Models\Branch;

class GeofenceService
{
    private Branch $branch;

    public function __construct()
    {
        $this->branch = new Branch();
    }

    /**
     * حساب المسافة بين نقطتين (Haversine)
     */
    public static function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // متر
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
        ));

        return $angle * $earthRadius;
    }

    /**
     * التحقق من النطاق الجغرافي
     */
    public function isWithinGeofence(float $empLat, float $empLon, ?int $branchId = null): array
    {
        $workLat = 0;
        $workLon = 0;
        $radius = 500;

        if ($branchId) {
            $schedule = $this->branch->getSchedule($branchId);
            if ($schedule) {
                $workLat = (float)$schedule['latitude'];
                $workLon = (float)$schedule['longitude'];
                $radius = (float)$schedule['geofence_radius'];
            }
        }

        if ($workLat == 0 && $workLon == 0) {
            $settings = loadAllSettings();
            $workLat = (float)($settings['work_latitude'] ?? '0');
            $workLon = (float)($settings['work_longitude'] ?? '0');
            $radius = (float)($settings['geofence_radius'] ?? '500');
        }

        if ($workLat == 0 && $workLon == 0) {
            return ['allowed' => true, 'distance' => 0, 'message' => 'موقع العمل غير محدد - مسموح'];
        }

        $distance = static::calculateDistance($empLat, $empLon, $workLat, $workLon);
        $allowed = $distance <= $radius;
        $dist = round($distance);

        return [
            'allowed' => $allowed,
            'distance' => $dist,
            'radius' => $radius,
            'message' => $allowed
                ? "أنت داخل نطاق العمل ({$dist} متر)"
                : "أنت خارج نطاق العمل! المسافة: {$dist} متر (الحد المسموح: {$radius} متر)",
        ];
    }
}
