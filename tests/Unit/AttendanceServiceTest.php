<?php

namespace Tests\Unit;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->branch   = Branch::factory()->create(['work_start_time' => '08:00:00']);
        $this->employee = Employee::factory()->create(['branch_id' => $this->branch->id]);
    }

    // ─── AttendanceService::isWithinTimeWindow ───────────────────────────────

    public function test_within_time_window_returns_true(): void
    {
        $this->assertTrue(AttendanceService::isWithinTimeWindow('09:00', '07:00', '11:00'));
    }

    public function test_outside_time_window_returns_false(): void
    {
        $this->assertFalse(AttendanceService::isWithinTimeWindow('12:00', '07:00', '10:00'));
    }

    public function test_exactly_at_start_of_window(): void
    {
        $this->assertTrue(AttendanceService::isWithinTimeWindow('07:00', '07:00', '10:00'));
    }

    public function test_exactly_at_end_of_window(): void
    {
        $this->assertTrue(AttendanceService::isWithinTimeWindow('10:00', '07:00', '10:00'));
    }

    public function test_overnight_window_outside_returns_false(): void
    {
        // نافذة ليلية: 22:00 → 02:00. الساعة 12:00 خارجها.
        $this->assertFalse(AttendanceService::isWithinTimeWindow('12:00', '22:00', '02:00'));
    }

    // ─── AttendanceService::record ───────────────────────────────────────────

    public function test_record_checkin_creates_attendance(): void
    {
        $result = AttendanceService::record(
            $this->employee->id, 'in', 24.7136, 46.6753
        );

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'type'        => 'in',
        ]);
    }

    public function test_record_checkout_creates_attendance(): void
    {
        $result = AttendanceService::record(
            $this->employee->id, 'out', 24.7136, 46.6753
        );

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'type'        => 'out',
        ]);
    }

    public function test_record_with_invalid_type_fails(): void
    {
        $result = AttendanceService::record(
            $this->employee->id, 'invalid-type', 24.7136, 46.6753
        );

        $this->assertFalse($result['success']);
    }

    public function test_duplicate_record_within_5_minutes_blocked(): void
    {
        Attendance::create([
            'employee_id'     => $this->employee->id,
            'type'            => 'in',
            'timestamp'       => now()->subMinutes(3),
            'attendance_date' => today()->toDateString(),
        ]);

        $result = AttendanceService::record(
            $this->employee->id, 'in', 24.7136, 46.6753
        );

        $this->assertFalse($result['success']);
    }

    public function test_record_after_5_minutes_allowed(): void
    {
        Attendance::create([
            'employee_id'     => $this->employee->id,
            'type'            => 'in',
            'timestamp'       => now()->subMinutes(6),
            'attendance_date' => today()->toDateString(),
        ]);

        $result = AttendanceService::record(
            $this->employee->id, 'in', 24.7136, 46.6753
        );

        $this->assertTrue($result['success']);
    }

    public function test_record_returns_message(): void
    {
        $result = AttendanceService::record(
            $this->employee->id, 'in', 24.7136, 46.6753
        );

        $this->assertArrayHasKey('message', $result);
        $this->assertNotEmpty($result['message']);
    }

    public function test_record_stores_coordinates(): void
    {
        AttendanceService::record(
            $this->employee->id, 'in', 24.7136, 46.6753, 10.0
        );

        $record = Attendance::where('employee_id', $this->employee->id)->latest()->first();
        $this->assertNotNull($record);
        $this->assertEquals(24.7136, (float) $record->latitude);
        $this->assertEquals(46.6753, (float) $record->longitude);
    }

    public function test_mock_location_stored_if_detected(): void
    {
        AttendanceService::record(
            $this->employee->id, 'in', 24.7136, 46.6753,
            5.0, true // mock_location = true
        );

        $record = Attendance::where('employee_id', $this->employee->id)->latest()->first();
        $this->assertTrue((bool) $record->mock_location_detected);
    }

    public function test_overtime_start_record(): void
    {
        $result = AttendanceService::record(
            $this->employee->id, 'overtime-start', 24.7136, 46.6753
        );

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'type'        => 'overtime-start',
        ]);
    }

    public function test_overtime_end_record(): void
    {
        $result = AttendanceService::record(
            $this->employee->id, 'overtime-end', 24.7136, 46.6753
        );

        $this->assertTrue($result['success']);
    }

    // ─── Attendance::hasRecentRecord ──────────────────────────────────────────

    public function test_has_recent_record_returns_true_within_window(): void
    {
        Attendance::create([
            'employee_id'     => $this->employee->id,
            'type'            => 'in',
            'timestamp'       => now()->subMinutes(2),
            'attendance_date' => today()->toDateString(),
        ]);

        $this->assertTrue(Attendance::hasRecentRecord($this->employee->id, 'in', 5));
    }

    public function test_has_recent_record_returns_false_outside_window(): void
    {
        Attendance::create([
            'employee_id'     => $this->employee->id,
            'type'            => 'in',
            'timestamp'       => now()->subMinutes(10),
            'attendance_date' => today()->toDateString(),
        ]);

        $this->assertFalse(Attendance::hasRecentRecord($this->employee->id, 'in', 5));
    }

    // ─── Attendance::getTodayStats ────────────────────────────────────────────

    public function test_today_stats_returns_correct_structure(): void
    {
        $stats = Attendance::getTodayStats();

        $this->assertArrayHasKey('checked_in', $stats);
        $this->assertArrayHasKey('checked_out', $stats);
        $this->assertArrayHasKey('total_employees', $stats);
    }

    public function test_today_stats_counts_correct_check_ins(): void
    {
        Attendance::create([
            'employee_id'     => $this->employee->id,
            'type'            => 'in',
            'timestamp'       => now(),
            'attendance_date' => today()->toDateString(),
        ]);

        $stats = Attendance::getTodayStats();
        $this->assertEquals(1, $stats['checked_in']);
    }
}
