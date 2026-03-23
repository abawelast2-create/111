<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Leave;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveModelTest extends TestCase
{
    use RefreshDatabase;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $branch         = Branch::factory()->create();
        $this->employee = Employee::factory()->create(['branch_id' => $branch->id]);
    }

    private function makeLeave(array $overrides = []): Leave
    {
        return Leave::create(array_merge([
            'employee_id' => $this->employee->id,
            'leave_type'  => 'annual',
            'start_date'  => now()->addDays(5)->toDateString(),
            'end_date'    => now()->addDays(9)->toDateString(),
            'status'      => 'pending',
        ], $overrides));
    }

    // ─── Leave::hasOverlapping ───────────────────────────────────────────────

    public function test_no_overlap_when_no_leaves_exist(): void
    {
        $result = Leave::hasOverlapping(
            $this->employee->id,
            now()->addDays(20)->toDateString(),
            now()->addDays(25)->toDateString()
        );

        $this->assertFalse($result);
    }

    public function test_overlap_detected_with_same_dates(): void
    {
        $this->makeLeave([
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date'   => now()->addDays(9)->toDateString(),
            'status'     => 'approved',
        ]);

        $result = Leave::hasOverlapping(
            $this->employee->id,
            now()->addDays(5)->toDateString(),
            now()->addDays(9)->toDateString()
        );

        $this->assertTrue($result);
    }

    public function test_overlap_detected_with_partial_overlap(): void
    {
        $this->makeLeave([
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date'   => now()->addDays(9)->toDateString(),
            'status'     => 'approved',
        ]);

        // جديدة تبدأ في منتصف القديمة
        $result = Leave::hasOverlapping(
            $this->employee->id,
            now()->addDays(7)->toDateString(),
            now()->addDays(12)->toDateString()
        );

        $this->assertTrue($result);
    }

    public function test_no_overlap_with_adjacent_leave(): void
    {
        $this->makeLeave([
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date'   => now()->addDays(4)->toDateString(),
            'status'     => 'approved',
        ]);

        $result = Leave::hasOverlapping(
            $this->employee->id,
            now()->addDays(5)->toDateString(),
            now()->addDays(9)->toDateString()
        );

        $this->assertFalse($result);
    }

    public function test_rejected_leave_does_not_cause_overlap(): void
    {
        $this->makeLeave([
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date'   => now()->addDays(9)->toDateString(),
            'status'     => 'rejected',
        ]);

        $result = Leave::hasOverlapping(
            $this->employee->id,
            now()->addDays(5)->toDateString(),
            now()->addDays(9)->toDateString()
        );

        $this->assertFalse($result);
    }

    public function test_exclude_id_prevents_self_overlap(): void
    {
        $leave = $this->makeLeave([
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date'   => now()->addDays(9)->toDateString(),
            'status'     => 'approved',
        ]);

        $result = Leave::hasOverlapping(
            $this->employee->id,
            now()->addDays(5)->toDateString(),
            now()->addDays(9)->toDateString(),
            $leave->id
        );

        $this->assertFalse($result);
    }

    // ─── Leave::isOnLeaveToday ───────────────────────────────────────────────

    public function test_employee_on_leave_today_returns_true(): void
    {
        Leave::create([
            'employee_id' => $this->employee->id,
            'leave_type'  => 'annual',
            'start_date'  => today()->subDays(1)->toDateString(),
            'end_date'    => today()->addDays(1)->toDateString(),
            'status'      => 'approved',
        ]);

        $this->assertTrue(Leave::isOnLeaveToday($this->employee->id));
    }

    public function test_employee_not_on_leave_today_returns_false(): void
    {
        Leave::create([
            'employee_id' => $this->employee->id,
            'leave_type'  => 'annual',
            'start_date'  => today()->addDays(5)->toDateString(),
            'end_date'    => today()->addDays(9)->toDateString(),
            'status'      => 'approved',
        ]);

        $this->assertFalse(Leave::isOnLeaveToday($this->employee->id));
    }

    public function test_pending_leave_today_does_not_count(): void
    {
        Leave::create([
            'employee_id' => $this->employee->id,
            'leave_type'  => 'annual',
            'start_date'  => today()->subDay()->toDateString(),
            'end_date'    => today()->addDay()->toDateString(),
            'status'      => 'pending',
        ]);

        $this->assertFalse(Leave::isOnLeaveToday($this->employee->id));
    }

    public function test_employee_with_no_leaves_not_on_leave(): void
    {
        $this->assertFalse(Leave::isOnLeaveToday($this->employee->id));
    }

    // ─── العلاقات ────────────────────────────────────────────────────────────

    public function test_leave_belongs_to_employee(): void
    {
        $leave = $this->makeLeave();
        $this->assertNotNull($leave->employee);
        $this->assertEquals($this->employee->id, $leave->employee->id);
    }
}
