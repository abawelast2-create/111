<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Leave;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveTest extends TestCase
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

    private function makeLeave(array $overrides = []): Leave
    {
        return Leave::create(array_merge([
            'employee_id' => $this->employee->id,
            'leave_type'  => 'annual',
            'start_date'  => now()->addDays(5)->toDateString(),
            'end_date'    => now()->addDays(7)->toDateString(),
            'status'      => 'pending',
        ], $overrides));
    }

    // ─── قائمة الإجازات ─────────────────────────────────────────────────────

    public function test_leaves_index_is_accessible(): void
    {
        $this->asAdmin()
             ->get(route('admin.leaves.index'))
             ->assertStatus(200);
    }

    public function test_leaves_index_shows_pending_count(): void
    {
        $this->makeLeave(['status' => 'pending']);
        $this->makeLeave(['status' => 'pending', 'start_date' => now()->addDays(10)->toDateString(), 'end_date' => now()->addDays(11)->toDateString()]);

        $this->asAdmin()
             ->get(route('admin.leaves.index'))
             ->assertStatus(200)
             ->assertViewHas('pendingCount', 2);
    }

    public function test_leaves_index_filters_by_status(): void
    {
        $this->makeLeave(['status' => 'pending']);
        $this->makeLeave([
            'status'     => 'approved',
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date'   => now()->addDays(11)->toDateString(),
        ]);

        $this->asAdmin()
             ->get(route('admin.leaves.index', ['status' => 'pending']))
             ->assertStatus(200)
             ->assertViewHas('leaves', fn ($p) => $p->total() === 1);
    }

    // ─── الموافقة على إجازة ─────────────────────────────────────────────────

    public function test_admin_can_approve_leave(): void
    {
        $leave = $this->makeLeave(['status' => 'pending']);

        $this->asAdmin()
             ->post(route('admin.leaves.approve', $leave))
             ->assertRedirect(route('admin.leaves.index'));

        $this->assertDatabaseHas('leaves', [
            'id'     => $leave->id,
            'status' => 'approved',
        ]);
    }

    public function test_approved_leave_records_approver(): void
    {
        $leave = $this->makeLeave();

        $this->asAdmin()
             ->post(route('admin.leaves.approve', $leave));

        $leave->refresh();
        $this->assertEquals($this->admin->id, $leave->approved_by);
    }

    // ─── رفض إجازة ──────────────────────────────────────────────────────────

    public function test_admin_can_reject_leave(): void
    {
        $leave = $this->makeLeave(['status' => 'pending']);

        $this->asAdmin()
             ->post(route('admin.leaves.reject', $leave))
             ->assertRedirect(route('admin.leaves.index'));

        $this->assertDatabaseHas('leaves', [
            'id'     => $leave->id,
            'status' => 'rejected',
        ]);
    }

    // ─── غير مصادق عليه ────────────────────────────────────────────────────

    public function test_unauthenticated_cannot_access_leaves(): void
    {
        $this->get(route('admin.leaves.index'))->assertRedirect(route('admin.login'));
    }
}
