<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Setting;

class ScheduleService
{
    public static function timeToMinutes(string $time): int
    {
        $p = explode(':', $time);
        return (int) $p[0] * 60 + (int) ($p[1] ?? 0);
    }

    public static function detectCurrentShift(array $s1, array $s2): int
    {
        $nowMin = (int) now()->format('H') * 60 + (int) now()->format('i');

        $s2CiStart = static::timeToMinutes($s2['check_in_start_time']);
        $s2CoEnd   = static::timeToMinutes($s2['check_out_end_time']);
        $s1CiStart = static::timeToMinutes($s1['check_in_start_time']);
        $s1CoEnd   = static::timeToMinutes($s1['check_out_end_time']);

        if ($s2CoEnd <= $s2CiStart) {
            if ($nowMin >= $s2CiStart || $nowMin <= $s2CoEnd + 180) {
                return 2;
            }
        } else {
            if ($nowMin >= $s2CiStart && $nowMin <= $s2CoEnd + 180) {
                return 2;
            }
        }

        if ($nowMin >= $s1CiStart && $nowMin <= $s1CoEnd + 120) {
            return 1;
        }

        $distS1 = ($s1CiStart - $nowMin + 1440) % 1440;
        $distS2 = ($s2CiStart - $nowMin + 1440) % 1440;
        return $distS2 < $distS1 ? 2 : 1;
    }

    public static function detectShiftByCheckinTime(string $checkinTimestamp, array $s1, array $s2): int
    {
        $ci = new \DateTime($checkinTimestamp);
        $ciMin = (int) $ci->format('H') * 60 + (int) $ci->format('i');

        $s1CiStart = static::timeToMinutes($s1['check_in_start_time']);
        $s1CiEnd   = static::timeToMinutes($s1['check_in_end_time']);
        $s2CiStart = static::timeToMinutes($s2['check_in_start_time']);
        $s2CiEnd   = static::timeToMinutes($s2['check_in_end_time']);

        if ($ciMin >= $s1CiStart && $ciMin <= $s1CiEnd + 60) {
            return 1;
        }
        if ($s2CiEnd < $s2CiStart) {
            if ($ciMin >= $s2CiStart || $ciMin <= $s2CiEnd + 60) return 2;
        } else {
            if ($ciMin >= $s2CiStart && $ciMin <= $s2CiEnd + 60) return 2;
        }
        return 1;
    }

    public static function getBranchSchedule(?int $branchId = null): array
    {
        $defaults = Setting::loadAll();

        $shift1 = [
            'work_start_time'      => $defaults['work_start_time'] ?? '12:00',
            'work_end_time'        => $defaults['work_end_time'] ?? '15:30',
            'check_in_start_time'  => $defaults['check_in_start_time'] ?? '11:00',
            'check_in_end_time'    => $defaults['check_in_end_time'] ?? '14:00',
            'check_out_start_time' => $defaults['check_out_start_time'] ?? '15:00',
            'check_out_end_time'   => $defaults['check_out_end_time'] ?? '15:30',
        ];

        $common = [
            'checkout_show_before'  => (int) ($defaults['checkout_show_before'] ?? 0),
            'allow_overtime'        => ($defaults['allow_overtime'] ?? '1') === '1',
            'overtime_start_after'  => (int) ($defaults['overtime_start_after'] ?? 60),
            'overtime_min_duration' => (int) ($defaults['overtime_min_duration'] ?? 30),
        ];

        $branchFound = false;
        if ($branchId) {
            $branch = Branch::where('id', $branchId)->where('is_active', true)->first();
            if ($branch) {
                $branchFound = true;
                $shift1 = [
                    'work_start_time'      => $branch->work_start_time,
                    'work_end_time'        => $branch->work_end_time,
                    'check_in_start_time'  => $branch->check_in_start_time,
                    'check_in_end_time'    => $branch->check_in_end_time,
                    'check_out_start_time' => $branch->check_out_start_time,
                    'check_out_end_time'   => $branch->check_out_end_time,
                ];
                $common = [
                    'checkout_show_before'  => (int) $branch->checkout_show_before,
                    'allow_overtime'        => (bool) $branch->allow_overtime,
                    'overtime_start_after'  => (int) $branch->overtime_start_after,
                    'overtime_min_duration' => (int) $branch->overtime_min_duration,
                ];
            }
        }

        if ($branchFound) {
            return array_merge($shift1, $common, [
                'current_shift' => 1,
                'shift1'        => $shift1,
                'shift2'        => $shift1,
            ]);
        }

        $shift2 = [
            'work_start_time'      => $defaults['work_start_time_2'] ?? '20:00',
            'work_end_time'        => $defaults['work_end_time_2'] ?? '00:00',
            'check_in_start_time'  => $defaults['check_in_start_time_2'] ?? '19:00',
            'check_in_end_time'    => $defaults['check_in_end_time_2'] ?? '22:00',
            'check_out_start_time' => $defaults['check_out_start_time_2'] ?? '23:30',
            'check_out_end_time'   => $defaults['check_out_end_time_2'] ?? '00:00',
        ];

        $currentShift = static::detectCurrentShift($shift1, $shift2);
        $active = $currentShift === 2 ? $shift2 : $shift1;

        return array_merge($active, $common, [
            'current_shift' => $currentShift,
            'shift1'        => $shift1,
            'shift2'        => $shift2,
        ]);
    }
}
