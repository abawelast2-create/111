<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Branch;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DataGeneratorService
{
    // Tag to identify generated records (stored in notes field)
    const GENERATED_TAG = '[GENERATED]';
    const BATCH_PREFIX  = '[BATCH:';

    /**
     * Discipline level profiles (1 = chaotic, 10 = perfect)
     * Each level defines realistic attendance behavior parameters
     */
    private static function getLevelProfile(int $level): array
    {
        $profiles = [
            1  => [
                'absence_rate'      => [0.30, 0.42],  // 30-42% absent
                'late_rate'         => [0.60, 0.80],  // 60-80% late when present
                'late_min'          => [25, 120],      // 25-120 min late
                'early_leave_rate'  => [0.25, 0.40],  // leaves early often
                'early_leave_min'   => [20, 90],       // 20-90 min early
                'overtime_rate'     => [0.00, 0.03],   // rarely does overtime
                'mock_gps_rate'     => [0.08, 0.15],   // occasional GPS spoofing
                'accuracy_range'    => [5, 800],       // wild GPS accuracy
                'checkin_variance'  => [-5, 120],      // arrives -5 to +120 min from start
                'checkout_variance' => [-90, 10],      // leaves -90 to +10 min from end
            ],
            2  => [
                'absence_rate'      => [0.22, 0.32],
                'late_rate'         => [0.48, 0.65],
                'late_min'          => [18, 90],
                'early_leave_rate'  => [0.18, 0.30],
                'early_leave_min'   => [15, 60],
                'overtime_rate'     => [0.02, 0.06],
                'mock_gps_rate'     => [0.04, 0.10],
                'accuracy_range'    => [6, 500],
                'checkin_variance'  => [-5, 90],
                'checkout_variance' => [-60, 15],
            ],
            3  => [
                'absence_rate'      => [0.16, 0.25],
                'late_rate'         => [0.36, 0.52],
                'late_min'          => [12, 60],
                'early_leave_rate'  => [0.12, 0.22],
                'early_leave_min'   => [10, 45],
                'overtime_rate'     => [0.04, 0.10],
                'mock_gps_rate'     => [0.02, 0.06],
                'accuracy_range'    => [8, 300],
                'checkin_variance'  => [-8, 60],
                'checkout_variance' => [-45, 20],
            ],
            4  => [
                'absence_rate'      => [0.11, 0.19],
                'late_rate'         => [0.26, 0.42],
                'late_min'          => [8, 45],
                'early_leave_rate'  => [0.08, 0.16],
                'early_leave_min'   => [8, 30],
                'overtime_rate'     => [0.06, 0.14],
                'mock_gps_rate'     => [0.01, 0.03],
                'accuracy_range'    => [10, 200],
                'checkin_variance'  => [-10, 45],
                'checkout_variance' => [-30, 25],
            ],
            5  => [
                'absence_rate'      => [0.07, 0.14],
                'late_rate'         => [0.18, 0.32],
                'late_min'          => [5, 30],
                'early_leave_rate'  => [0.05, 0.12],
                'early_leave_min'   => [5, 20],
                'overtime_rate'     => [0.08, 0.18],
                'mock_gps_rate'     => [0.00, 0.02],
                'accuracy_range'    => [12, 150],
                'checkin_variance'  => [-12, 30],
                'checkout_variance' => [-20, 30],
            ],
            6  => [
                'absence_rate'      => [0.05, 0.10],
                'late_rate'         => [0.12, 0.23],
                'late_min'          => [3, 20],
                'early_leave_rate'  => [0.03, 0.08],
                'early_leave_min'   => [3, 15],
                'overtime_rate'     => [0.10, 0.22],
                'mock_gps_rate'     => [0.00, 0.01],
                'accuracy_range'    => [14, 100],
                'checkin_variance'  => [-15, 20],
                'checkout_variance' => [-15, 35],
            ],
            7  => [
                'absence_rate'      => [0.03, 0.07],
                'late_rate'         => [0.07, 0.16],
                'late_min'          => [2, 15],
                'early_leave_rate'  => [0.02, 0.05],
                'early_leave_min'   => [2, 10],
                'overtime_rate'     => [0.12, 0.26],
                'mock_gps_rate'     => [0.00, 0.005],
                'accuracy_range'    => [15, 70],
                'checkin_variance'  => [-18, 15],
                'checkout_variance' => [-10, 40],
            ],
            8  => [
                'absence_rate'      => [0.02, 0.05],
                'late_rate'         => [0.04, 0.10],
                'late_min'          => [1, 10],
                'early_leave_rate'  => [0.01, 0.03],
                'early_leave_min'   => [1, 8],
                'overtime_rate'     => [0.15, 0.30],
                'mock_gps_rate'     => [0.00, 0.00],
                'accuracy_range'    => [16, 50],
                'checkin_variance'  => [-20, 10],
                'checkout_variance' => [-8, 50],
            ],
            9  => [
                'absence_rate'      => [0.01, 0.03],
                'late_rate'         => [0.01, 0.05],
                'late_min'          => [1, 5],
                'early_leave_rate'  => [0.005, 0.02],
                'early_leave_min'   => [1, 5],
                'overtime_rate'     => [0.18, 0.35],
                'mock_gps_rate'     => [0.00, 0.00],
                'accuracy_range'    => [18, 40],
                'checkin_variance'  => [-25, 5],
                'checkout_variance' => [-5, 55],
            ],
            10 => [
                'absence_rate'      => [0.00, 0.01],
                'late_rate'         => [0.00, 0.02],
                'late_min'          => [1, 3],
                'early_leave_rate'  => [0.00, 0.01],
                'early_leave_min'   => [1, 3],
                'overtime_rate'     => [0.20, 0.40],
                'mock_gps_rate'     => [0.00, 0.00],
                'accuracy_range'    => [20, 35],
                'checkin_variance'  => [-30, 3],
                'checkout_variance' => [-3, 60],
            ],
        ];

        return $profiles[$level] ?? $profiles[5];
    }

    /**
     * Generate attendance data
     *
     * @param string $from       Start date (Y-m-d)
     * @param string $to         End date (Y-m-d)
     * @param int    $level      Discipline level 1-10
     * @param string $scope      'all', 'branch', 'employee'
     * @param int|null $scopeId  Branch ID or Employee ID (null for 'all')
     * @return array             Summary of generated records
     */
    public static function generate(string $from, string $to, int $level = 5, string $scope = 'all', ?int $scopeId = null): array
    {
        $level = max(1, min(10, $level));
        $profile = self::getLevelProfile($level);
        $batchId = 'B' . now()->format('YmdHis') . '_' . mt_rand(1000, 9999);

        // Get target employees
        $employees = self::getEmployees($scope, $scopeId);

        if ($employees->isEmpty()) {
            return ['error' => 'لم يتم العثور على موظفين', 'count' => 0];
        }

        // Get work days in range (exclude Friday & Saturday - Saudi weekend)
        $workDays = self::getWorkDays($from, $to);

        $totalRecords = 0;
        $stats = [
            'batch_id'       => $batchId,
            'employees'      => $employees->count(),
            'work_days'      => count($workDays),
            'level'          => $level,
            'total_records'  => 0,
            'checkins'       => 0,
            'checkouts'      => 0,
            'overtime'       => 0,
            'absences'       => 0,
            'late_records'   => 0,
            'mock_gps'       => 0,
        ];

        // Process in chunks to avoid memory issues
        $insertBatch = [];
        $batchSize = 500;

        foreach ($employees as $employee) {
            $branch = $employee->branch;
            if (!$branch) {
                continue;
            }

            // Each employee gets slight personal variation within the level
            $personalProfile = self::personalize($profile);

            foreach ($workDays as $day) {
                $records = self::generateDayRecords($employee, $branch, $day, $personalProfile, $batchId);

                if (empty($records)) {
                    $stats['absences']++;
                    continue;
                }

                foreach ($records as $record) {
                    $insertBatch[] = $record;
                    $stats['total_records']++;

                    // Count by type
                    match ($record['type']) {
                        'in'             => $stats['checkins']++,
                        'out'            => $stats['checkouts']++,
                        'overtime-start',
                        'overtime-end'   => $stats['overtime']++,
                        default          => null,
                    };

                    if ($record['late_minutes'] > 0) {
                        $stats['late_records']++;
                    }
                    if ($record['mock_location_detected']) {
                        $stats['mock_gps']++;
                    }
                }

                // Flush batch
                if (count($insertBatch) >= $batchSize) {
                    Attendance::insert($insertBatch);
                    $insertBatch = [];
                }
            }
        }

        // Insert remaining
        if (!empty($insertBatch)) {
            Attendance::insert($insertBatch);
        }

        return $stats;
    }

    /**
     * Generate records for one employee for one day
     */
    private static function generateDayRecords(Employee $employee, Branch $branch, Carbon $day, array $profile, string $batchId): array
    {
        // Decide if absent
        $absenceRate = self::randFloat($profile['absence_rate'][0], $profile['absence_rate'][1]);
        if (self::chance($absenceRate)) {
            return []; // absent this day
        }

        $records = [];
        $now = now();

        // Get work schedule
        $schedule = $employee->getFlexibleSchedule();
        $workStart = $schedule ? $schedule['start'] : ($branch->work_start_time ?? '08:00:00');
        $workEnd   = $schedule ? $schedule['end']   : ($branch->work_end_time ?? '16:00:00');

        $workStartTime = Carbon::parse($day->format('Y-m-d') . ' ' . $workStart);
        $workEndTime   = Carbon::parse($day->format('Y-m-d') . ' ' . $workEnd);

        // === CHECK-IN ===
        $varianceMin = mt_rand((int)$profile['checkin_variance'][0], (int)$profile['checkin_variance'][1]);
        $checkinTime = $workStartTime->copy()->addMinutes($varianceMin);

        // Calculate late minutes
        $lateMinutes = 0;
        if ($checkinTime->gt($workStartTime)) {
            // Apply grace period from flexible schedule or branch
            $grace = $schedule ? ($schedule['window'] ?? 0) : 0;
            $actualLate = max(0, $varianceMin - $grace);

            // Decide if this is a "late day" based on profile
            $lateRate = self::randFloat($profile['late_rate'][0], $profile['late_rate'][1]);
            if ($actualLate > 0 && self::chance($lateRate)) {
                $lateMinutes = min($actualLate, mt_rand((int)$profile['late_min'][0], (int)$profile['late_min'][1]));
            }
        }

        // GPS coordinates with realistic scatter around branch center
        [$lat, $lng] = self::scatterGps($branch->latitude, $branch->longitude, $branch->geofence_radius);
        $accuracy = self::randFloat($profile['accuracy_range'][0], $profile['accuracy_range'][1]);

        // Mock GPS detection
        $mockGps = self::chance(self::randFloat($profile['mock_gps_rate'][0], $profile['mock_gps_rate'][1]));

        // Validation score (correlates with level)
        $baseScore = max(10, min(100, 50 + ($profile['level_bonus'] ?? 0) + mt_rand(-10, 10)));
        if ($mockGps) {
            $baseScore = max(5, $baseScore - mt_rand(25, 50));
        }

        $wifiNetworks = $branch->wifi_ssids
            ? json_encode(array_slice(explode(',', $branch->wifi_ssids), 0, mt_rand(1, 3)))
            : null;

        $records[] = self::buildRecord(
            $employee->id, 'in', $checkinTime, $day, $lateMinutes,
            $lat, $lng, $accuracy, $mockGps, $baseScore,
            $wifiNetworks, $batchId, $now
        );

        // === CHECK-OUT ===
        $checkoutVariance = mt_rand((int)$profile['checkout_variance'][0], (int)$profile['checkout_variance'][1]);
        $checkoutTime = $workEndTime->copy()->addMinutes($checkoutVariance);

        // Ensure checkout is after checkin
        if ($checkoutTime->lte($checkinTime)) {
            $checkoutTime = $checkinTime->copy()->addHours(mt_rand(4, 7))->addMinutes(mt_rand(0, 59));
        }

        [$lat2, $lng2] = self::scatterGps($branch->latitude, $branch->longitude, $branch->geofence_radius);

        $records[] = self::buildRecord(
            $employee->id, 'out', $checkoutTime, $day, 0,
            $lat2, $lng2, $accuracy + self::randFloat(-5, 5),
            false, $baseScore + mt_rand(-5, 5),
            $wifiNetworks, $batchId, $now
        );

        // === OVERTIME (optional) ===
        $overtimeRate = self::randFloat($profile['overtime_rate'][0], $profile['overtime_rate'][1]);
        if ($branch->allow_overtime && self::chance($overtimeRate)) {
            $otStart = $checkoutTime->copy()->addMinutes(mt_rand(5, 30));
            $otDuration = mt_rand(
                max(30, $branch->overtime_min_duration ?? 30),
                max(60, ($branch->overtime_min_duration ?? 30) + 120)
            );
            $otEnd = $otStart->copy()->addMinutes($otDuration);

            [$lat3, $lng3] = self::scatterGps($branch->latitude, $branch->longitude, $branch->geofence_radius);

            $records[] = self::buildRecord(
                $employee->id, 'overtime-start', $otStart, $day, 0,
                $lat3, $lng3, $accuracy, false, $baseScore,
                $wifiNetworks, $batchId, $now
            );

            $records[] = self::buildRecord(
                $employee->id, 'overtime-end', $otEnd, $day, 0,
                $lat3, $lng3, $accuracy, false, $baseScore,
                $wifiNetworks, $batchId, $now
            );
        }

        return $records;
    }

    /**
     * Build a single attendance record array for bulk insert
     */
    private static function buildRecord(
        int $employeeId, string $type, Carbon $timestamp, Carbon $day,
        int $lateMinutes, float $lat, float $lng, float $accuracy,
        bool $mockGps, float $validationScore, ?string $wifiNetworks,
        string $batchId, Carbon $now
    ): array {
        // Generate realistic IP
        $ipPrefix = ['192.168.1.', '10.0.0.', '172.16.0.', '192.168.0.'];
        $ip = $ipPrefix[array_rand($ipPrefix)] . mt_rand(2, 254);

        $agents = [
            'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 Chrome/120.0',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) AppleWebKit/605.1',
            'Mozilla/5.0 (Linux; Android 14; SM-A546B) Chrome/121.0',
            'Mozilla/5.0 (Linux; Android 12; Redmi Note 11) Chrome/119.0',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6) AppleWebKit/605.1',
        ];

        return [
            'employee_id'           => $employeeId,
            'type'                  => $type,
            'timestamp'             => $timestamp->format('Y-m-d H:i:s'),
            'attendance_date'       => $day->format('Y-m-d'),
            'late_minutes'          => $lateMinutes,
            'latitude'              => round($lat, 8),
            'longitude'             => round($lng, 8),
            'location_accuracy'     => round(max(1, $accuracy), 2),
            'mock_location_detected'=> $mockGps ? 1 : 0,
            'validation_score'      => round(max(0, min(100, $validationScore)), 2),
            'wifi_networks'         => $wifiNetworks,
            'ip_location_match'     => $mockGps ? 0 : 1,
            'ip_address'            => $ip,
            'user_agent'            => $agents[array_rand($agents)],
            'notes'                 => self::GENERATED_TAG . ' ' . self::BATCH_PREFIX . $batchId . ']',
            'created_at'            => $now->format('Y-m-d H:i:s'),
            'updated_at'            => $now->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Personalize profile for each employee (natural variation)
     */
    private static function personalize(array $profile): array
    {
        // Add small random shifts so each employee behaves uniquely
        $factor = self::randFloat(0.7, 1.3);
        $personalized = $profile;

        $personalized['absence_rate'][0] *= $factor;
        $personalized['absence_rate'][1] *= $factor;
        $personalized['late_rate'][0]    *= self::randFloat(0.75, 1.25);
        $personalized['late_rate'][1]    *= self::randFloat(0.75, 1.25);
        $personalized['overtime_rate'][0] *= self::randFloat(0.5, 1.5);
        $personalized['overtime_rate'][1] *= self::randFloat(0.5, 1.5);

        // Shift checkin variance slightly per person
        $personalShift = mt_rand(-5, 5);
        $personalized['checkin_variance'][0] += $personalShift;
        $personalized['checkin_variance'][1] += $personalShift;

        // Level bonus for validation score
        $levelBonus = match (true) {
            $profile['mock_gps_rate'][1] >= 0.08 => mt_rand(-20, -5),
            $profile['mock_gps_rate'][1] >= 0.02 => mt_rand(-10, 5),
            $profile['mock_gps_rate'][1] > 0     => mt_rand(0, 15),
            default                              => mt_rand(15, 35),
        };
        $personalized['level_bonus'] = $levelBonus;

        return $personalized;
    }

    /**
     * Get work days (exclude Friday & Saturday - Saudi weekend)
     */
    private static function getWorkDays(string $from, string $to): array
    {
        $period = CarbonPeriod::create($from, $to);
        $workDays = [];

        foreach ($period as $day) {
            // Friday = 5, Saturday = 6 (Saudi weekend)
            if (!in_array($day->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY])) {
                $workDays[] = $day->copy();
            }
        }

        return $workDays;
    }

    /**
     * Get employees based on scope
     */
    private static function getEmployees(string $scope, ?int $scopeId)
    {
        $query = Employee::with('branch')->where('is_active', true);

        return match ($scope) {
            'employee' => $query->where('id', $scopeId)->get(),
            'branch'   => $query->where('branch_id', $scopeId)->get(),
            default    => $query->whereNotNull('branch_id')->get(),
        };
    }

    /**
     * Scatter GPS coordinates around a center point within a radius
     */
    private static function scatterGps(float $centerLat, float $centerLng, int $radiusMeters): array
    {
        // Random distance within 90% of geofence radius (mostly inside)
        $maxDist = $radiusMeters * 0.9;
        $distance = self::randFloat(0, $maxDist);
        $angle = self::randFloat(0, 2 * M_PI);

        // Convert meters to degrees (approximate)
        $dLat = ($distance * cos($angle)) / 111320;
        $dLng = ($distance * sin($angle)) / (111320 * cos(deg2rad($centerLat)));

        return [
            $centerLat + $dLat,
            $centerLng + $dLng,
        ];
    }

    /**
     * Remove generated records
     *
     * @param string|null $batchId  Specific batch, or null for all generated
     * @return int Number of deleted records
     */
    public static function cleanup(?string $batchId = null): int
    {
        $query = Attendance::where('notes', 'LIKE', self::GENERATED_TAG . '%');

        if ($batchId) {
            $query->where('notes', 'LIKE', '%' . self::BATCH_PREFIX . $batchId . ']%');
        }

        return $query->delete();
    }

    /**
     * Get list of generation batches
     */
    public static function getBatches(): array
    {
        $records = Attendance::where('notes', 'LIKE', self::GENERATED_TAG . '%')
            ->selectRaw("SUBSTRING_INDEX(SUBSTRING_INDEX(notes, '[BATCH:', -1), ']', 1) as batch_id")
            ->selectRaw('COUNT(*) as record_count')
            ->selectRaw('MIN(attendance_date) as from_date')
            ->selectRaw('MAX(attendance_date) as to_date')
            ->selectRaw('MIN(created_at) as created_at')
            ->groupByRaw("SUBSTRING_INDEX(SUBSTRING_INDEX(notes, '[BATCH:', -1), ']', 1)")
            ->orderByDesc('created_at')
            ->get();

        return $records->toArray();
    }

    /**
     * Preview: calculate expected records without inserting
     */
    public static function preview(string $from, string $to, string $scope = 'all', ?int $scopeId = null): array
    {
        $employees = self::getEmployees($scope, $scopeId);
        $workDays = self::getWorkDays($from, $to);

        return [
            'employees' => $employees->count(),
            'work_days' => count($workDays),
            'estimated_records' => $employees->count() * count($workDays) * 2, // ~2 records per day (in + out)
            'date_range' => $from . ' → ' . $to,
        ];
    }

    // === Utility Methods ===

    private static function chance(float $probability): bool
    {
        return mt_rand(0, 10000) / 10000 <= $probability;
    }

    private static function randFloat(float $min, float $max): float
    {
        return $min + mt_rand(0, 10000) / 10000 * ($max - $min);
    }
}
