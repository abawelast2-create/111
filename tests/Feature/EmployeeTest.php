<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin  = Admin::factory()->superAdmin()->create();
        $this->branch = Branch::factory()->create();
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

    // ─── عرض القائمة ───────────────────────────────────────────────────────

    public function test_employees_index_is_accessible(): void
    {
        $this->asAdmin()
             ->get(route('admin.employees.index'))
             ->assertStatus(200);
    }

    public function test_employees_index_shows_employees(): void
    {
        Employee::factory()->count(3)->create(['branch_id' => $this->branch->id]);

        $this->asAdmin()
             ->get(route('admin.employees.index'))
             ->assertStatus(200)
             ->assertViewHas('employees');
    }

    public function test_employees_index_search_by_name(): void
    {
        Employee::factory()->create(['branch_id' => $this->branch->id, 'name' => 'Abdullah Mohammed']);
        Employee::factory()->create(['branch_id' => $this->branch->id, 'name' => 'Khalid Salem']);

        $response = $this->asAdmin()
                         ->get(route('admin.employees.index', ['search' => 'Abdullah']));

        $response->assertStatus(200)
                 ->assertViewHas('employees', fn ($p) => $p->total() === 1);
    }

    public function test_employees_index_filter_by_branch(): void
    {
        $otherBranch = Branch::factory()->create();
        Employee::factory()->count(2)->create(['branch_id' => $this->branch->id]);
        Employee::factory()->count(3)->create(['branch_id' => $otherBranch->id]);

        $response = $this->asAdmin()
                         ->get(route('admin.employees.index', ['branch_id' => $this->branch->id]));

        $response->assertStatus(200)
                 ->assertViewHas('employees', fn ($p) => $p->total() === 2);
    }

    // ─── إضافة موظف ────────────────────────────────────────────────────────

    public function test_can_create_employee(): void
    {
        $this->asAdmin()
             ->post(route('admin.employees.store'), [
                 'name'      => 'Ali Hassan',
                 'job_title' => 'Engineer',
                 'phone'     => '0501234567',
                 'branch_id' => $this->branch->id,
             ])->assertRedirect(route('admin.employees.index'));

        $this->assertDatabaseHas('employees', ['name' => 'Ali Hassan']);
    }

    public function test_created_employee_has_pin_and_token(): void
    {
        $this->asAdmin()
             ->post(route('admin.employees.store'), [
                 'name'      => 'Omar Saleh',
                 'job_title' => 'Accountant',
                 'branch_id' => $this->branch->id,
             ]);

        $employee = Employee::where('name', 'Omar Saleh')->first();
        $this->assertNotNull($employee);
        $this->assertNotNull($employee->pin);
        $this->assertNotNull($employee->unique_token);
        $this->assertEquals(64, strlen($employee->unique_token));
    }

    public function test_create_employee_requires_name_and_job_title(): void
    {
        $this->asAdmin()
             ->post(route('admin.employees.store'), [])
             ->assertSessionHasErrors(['name', 'job_title']);
    }

    public function test_create_employee_branch_id_must_exist(): void
    {
        $this->asAdmin()
             ->post(route('admin.employees.store'), [
                 'name'      => 'Test',
                 'job_title' => 'Developer',
                 'branch_id' => 9999,
             ])->assertSessionHasErrors('branch_id');
    }

    // ─── تعديل موظف ────────────────────────────────────────────────────────

    public function test_can_update_employee(): void
    {
        $employee = Employee::factory()->create(['branch_id' => $this->branch->id]);

        $this->asAdmin()
             ->put(route('admin.employees.update', $employee), [
                 'name'      => 'Updated Name',
                 'job_title' => 'Senior Developer',
                 'branch_id' => $this->branch->id,
             ])->assertRedirect(route('admin.employees.index'));

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'name' => 'Updated Name']);
    }

    public function test_update_employee_requires_name_field(): void
    {
        $employee = Employee::factory()->create(['branch_id' => $this->branch->id]);

        $this->asAdmin()
             ->put(route('admin.employees.update', $employee), [
                 'job_title' => 'Developer',
             ])->assertSessionHasErrors('name');
    }

    // ─── حذف موظف (Soft Delete) ─────────────────────────────────────────────

    public function test_can_soft_delete_employee(): void
    {
        $employee = Employee::factory()->create(['branch_id' => $this->branch->id]);

        $this->asAdmin()
             ->delete(route('admin.employees.destroy', $employee))
             ->assertRedirect(route('admin.employees.index'));

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_soft_deleted_employee_not_in_default_listing(): void
    {
        $employee = Employee::factory()->deleted()->create(['branch_id' => $this->branch->id]);

        $this->asAdmin()
             ->get(route('admin.employees.index'))
             ->assertViewHas('employees', fn ($p) => $p->total() === 0);
    }

    // ─── استعادة موظف ──────────────────────────────────────────────────────

    public function test_can_restore_deleted_employee(): void
    {
        $employee = Employee::factory()->deleted()->create(['branch_id' => $this->branch->id]);

        $this->asAdmin()
             ->post(route('admin.employees.restore', $employee->id))
             ->assertRedirect(route('admin.employees.index'));

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'deleted_at' => null]);
    }

    // ─── تجديد PIN ─────────────────────────────────────────────────────────

    public function test_can_regenerate_pin(): void
    {
        $employee = Employee::factory()->create(['branch_id' => $this->branch->id]);
        $oldPin   = $employee->pin;

        $response = $this->asAdmin()
                         ->post(route('admin.employees.regenerate-pin', $employee));

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonStructure(['pin']);

        $employee->refresh();
        $this->assertNotEquals($oldPin, $employee->pin);
    }

    // ─── إعادة تعيين الجهاز ────────────────────────────────────────────────

    public function test_can_reset_device(): void
    {
        $employee = Employee::factory()->create([
            'branch_id'          => $this->branch->id,
            'device_fingerprint' => 'some-fingerprint',
        ]);

        $this->asAdmin()
             ->post(route('admin.employees.reset-device', $employee))
             ->assertStatus(200)
             ->assertJson(['success' => true]);

        $employee->refresh();
        $this->assertNull($employee->device_fingerprint);
    }

    // ─── غير مصادق عليه ────────────────────────────────────────────────────

    public function test_unauthenticated_cannot_access_employee_routes(): void
    {
        $this->get(route('admin.employees.index'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.employees.store'), [])->assertRedirect(route('admin.login'));
    }
}
