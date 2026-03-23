<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * اختبارات API العام (Sanctum tokens)
 */
class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->superAdmin()->create();
    }

    // ─── مصادقة ───────────────────────────────────────────────────

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v1/attendance')->assertStatus(401);
        $this->getJson('/api/v1/employees')->assertStatus(401);
        $this->getJson('/api/v1/branches')->assertStatus(401);
    }

    // ─── قائمة الحضور ─────────────────────────────────────────────

    public function test_can_list_attendance(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        $this->getJson('/api/v1/attendance')
             ->assertStatus(200)
             ->assertJsonStructure(['data']);
    }

    public function test_attendance_list_returns_correct_structure(): void
    {
        $branch   = Branch::factory()->create();
        $employee = Employee::factory()->create(['branch_id' => $branch->id]);
        Attendance::create([
            'employee_id'     => $employee->id,
            'type'            => 'in',
            'timestamp'       => now(),
            'attendance_date' => today()->toDateString(),
        ]);

        Sanctum::actingAs($this->admin, ['*']);

        $this->getJson('/api/v1/attendance')
             ->assertStatus(200)
             ->assertJsonStructure(['data' => [['id', 'employee_id', 'type', 'timestamp']]]);
    }

    // ─── قائمة الموظفين ───────────────────────────────────────────

    public function test_can_list_employees(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        $this->getJson('/api/v1/employees')
             ->assertStatus(200)
             ->assertJsonStructure(['data']);
    }

    public function test_employees_list_includes_created_employee(): void
    {
        $branch   = Branch::factory()->create();
        $employee = Employee::factory()->create(['branch_id' => $branch->id]);

        Sanctum::actingAs($this->admin, ['*']);

        $this->getJson('/api/v1/employees')
             ->assertStatus(200)
             ->assertJsonFragment(['id' => $employee->id]);
    }

    // ─── قائمة الفروع ─────────────────────────────────────────────

    public function test_can_list_branches(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        $this->getJson('/api/v1/branches')
             ->assertStatus(200)
             ->assertJsonStructure(['data']);
    }

    // ─── قائمة الإجازات ───────────────────────────────────────────

    public function test_can_list_leaves(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        $this->getJson('/api/v1/leaves')
             ->assertStatus(200)
             ->assertJsonStructure(['data']);
    }

    // ─── إنشاء موظف ───────────────────────────────────────────────

    public function test_can_create_employee_via_api(): void
    {
        Sanctum::actingAs($this->admin, ['*']);
        $branch = Branch::factory()->create();

        $this->postJson('/api/v1/employees', [
            'name'      => 'Ahmed Test',
            'branch_id' => $branch->id,
            'job_title' => 'Developer',
        ])->assertStatus(201);

        $this->assertDatabaseHas('employees', ['name' => 'Ahmed Test']);
    }

    // ─── إنشاء إجازة ──────────────────────────────────────────────

    public function test_can_create_leave_via_api(): void
    {
        Sanctum::actingAs($this->admin, ['*']);
        $branch   = Branch::factory()->create();
        $employee = Employee::factory()->create(['branch_id' => $branch->id]);

        $this->postJson('/api/v1/leaves', [
            'employee_id' => $employee->id,
            'leave_type'  => 'annual',
            'start_date'  => now()->addDays(5)->toDateString(),
            'end_date'    => now()->addDays(7)->toDateString(),
        ])->assertStatus(201);

        $this->assertDatabaseHas('leaves', ['employee_id' => $employee->id]);
    }

    // ─── تصفية الحضور ─────────────────────────────────────────────

    public function test_attendance_can_be_filtered_by_date(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        $this->getJson('/api/v1/attendance?date=' . today()->toDateString())
             ->assertStatus(200);
    }
}
