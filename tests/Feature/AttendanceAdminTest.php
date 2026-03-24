<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceAdminTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin    = Admin::factory()->superAdmin()->create();
        $branch         = Branch::factory()->create();
        $this->employee = Employee::factory()->create(['branch_id' => $branch->id]);
    }

    private function asAdmin(): static
    {
        return $this->withSession([
            'admin_id'      => $this->admin->id,
            'admin_username'=> $this->admin->username,
            'admin_name'    => $this->admin->full_name,
            'last_activity' => time(),
        ]);
    }

    private function makeAttendance(string $type = 'in', array $overrides = []): Attendance
    {
        return Attendance::create(array_merge([
            'employee_id'     => $this->employee->id,
            'type'            => $type,
            'timestamp'       => now(),
            'attendance_date' => today()->toDateString(),
            'late_minutes'    => 0,
            'latitude'        => 24.7136,
            'longitude'       => 46.6753,
        ], $overrides));
    }

    // ─── عرض صفحة الحضور ───────────────────────────────────────────────────

    public function test_attendance_page_is_accessible(): void
    {
        $this->asAdmin()
             ->get(route('admin.attendance.index'))
             ->assertStatus(200);
    }

    public function test_attendance_page_shows_records(): void
    {
        $this->makeAttendance('in');
        $this->makeAttendance('out');

        $this->asAdmin()
             ->get(route('admin.attendance.index'))
             ->assertStatus(200)
             ->assertViewHas('attendances');
    }

    public function test_attendance_filtered_by_date(): void
    {
        $this->makeAttendance('in', ['attendance_date' => today()->toDateString()]);
        $this->makeAttendance('in', ['attendance_date' => today()->subDays(3)->toDateString()]);

        $response = $this->asAdmin()
                         ->get(route('admin.attendance.index', ['date' => today()->toDateString()]));

        $response->assertStatus(200)
                 ->assertViewHas('attendances');
    }

    public function test_attendance_filtered_by_type(): void
    {
        $this->makeAttendance('in');
        $this->makeAttendance('out');

        $response = $this->asAdmin()
                         ->get(route('admin.attendance.index', ['type' => 'in']));

        $response->assertStatus(200)
                 ->assertViewHas('attendances', fn ($p) => $p->every(fn ($r) => $r->type === 'in'));
    }

    // ─── حذف سجل حضور ──────────────────────────────────────────────────────

    public function test_can_delete_attendance_record(): void
    {
        $record = $this->makeAttendance('in');

        $this->asAdmin()
             ->delete(route('admin.attendance.destroy', $record))
             ->assertRedirect();

        $this->assertDatabaseMissing('attendances', ['id' => $record->id]);
    }

    // ─── تقرير التأخيرات ────────────────────────────────────────────────────

    public function test_late_report_page_is_accessible(): void
    {
        $this->asAdmin()
             ->get(route('admin.late-report'))
             ->assertStatus(200);
    }

    public function test_late_report_shows_employees_with_late_minutes(): void
    {
        $this->makeAttendance('in', ['late_minutes' => 15]);
        $this->makeAttendance('in', ['late_minutes' => 30]);

        $this->asAdmin()
             ->get(route('admin.late-report'))
             ->assertStatus(200);
    }

    // ─── إحصائيات اليوم ─────────────────────────────────────────────────────

    public function test_today_stats_endpoint(): void
    {
        $this->makeAttendance('in');

        $this->asAdmin()
             ->getJson(route('admin.attendance.todayStats'))
             ->assertStatus(200)
             ->assertJsonStructure(['checked_in', 'checked_out', 'total_employees']);
    }

    // ─── غير مصادق عليه ────────────────────────────────────────────────────

    public function test_unauthenticated_cannot_access_attendance_admin(): void
    {
        $this->get(route('admin.attendance.index'))->assertRedirect(route('admin.login'));
    }
}
