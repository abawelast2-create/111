<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->superAdmin()->create();
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

    private function validBranchData(array $overrides = []): array
    {
        return array_merge([
            'name'                 => 'فرع جديد ' . uniqid(),
            'latitude'             => 24.7136,
            'longitude'            => 46.6753,
            'geofence_radius'      => 500,
            'work_start_time'      => '08:00',
            'work_end_time'        => '16:00',
            'check_in_start_time'  => '07:00',
            'check_in_end_time'    => '10:00',
            'check_out_start_time' => '15:00',
            'check_out_end_time'   => '20:00',
        ], $overrides);
    }

    // ─── عرض القائمة ───────────────────────────────────────────────────────

    public function test_branches_index_is_accessible(): void
    {
        $this->asAdmin()
             ->get(route('admin.branches.index'))
             ->assertStatus(200);
    }

    public function test_branches_index_shows_branches(): void
    {
        Branch::factory()->count(3)->create();

        $this->asAdmin()
             ->get(route('admin.branches.index'))
             ->assertStatus(200)
             ->assertViewHas('branches');
    }

    public function test_branches_view_includes_employee_count(): void
    {
        $branch = Branch::factory()->create();
        Employee::factory()->count(4)->create(['branch_id' => $branch->id]);

        $this->asAdmin()
             ->get(route('admin.branches.index'))
             ->assertStatus(200);
    }

    // ─── إضافة فرع ────────────────────────────────────────────────────────

    public function test_can_create_branch(): void
    {
        $this->asAdmin()
             ->post(route('admin.branches.store'), $this->validBranchData(['name' => 'الفرع الرئيسي']))
             ->assertRedirect(route('admin.branches.index'));

        $this->assertDatabaseHas('branches', ['name' => 'الفرع الرئيسي']);
    }

    public function test_create_branch_requires_name(): void
    {
        $data = $this->validBranchData();
        unset($data['name']);

        $this->asAdmin()
             ->post(route('admin.branches.store'), $data)
             ->assertSessionHasErrors('name');
    }

    public function test_create_branch_requires_coordinates(): void
    {
        $data = $this->validBranchData();
        unset($data['latitude'], $data['longitude']);

        $this->asAdmin()
             ->post(route('admin.branches.store'), $data)
             ->assertSessionHasErrors(['latitude', 'longitude']);
    }

    public function test_create_branch_requires_unique_name(): void
    {
        Branch::factory()->create(['name' => 'فرع مكرر']);

        $this->asAdmin()
             ->post(route('admin.branches.store'), $this->validBranchData(['name' => 'فرع مكرر']))
             ->assertSessionHasErrors('name');
    }

    public function test_geofence_radius_must_be_at_least_10(): void
    {
        $this->asAdmin()
             ->post(route('admin.branches.store'), $this->validBranchData(['geofence_radius' => 5]))
             ->assertSessionHasErrors('geofence_radius');
    }

    public function test_create_branch_requires_time_fields(): void
    {
        $this->asAdmin()
             ->post(route('admin.branches.store'), ['name' => 'فرع', 'latitude' => 24.7, 'longitude' => 46.6, 'geofence_radius' => 100])
             ->assertSessionHasErrors(['work_start_time', 'work_end_time', 'check_in_start_time', 'check_in_end_time']);
    }

    // ─── تعديل فرع ────────────────────────────────────────────────────────

    public function test_can_update_branch(): void
    {
        $branch = Branch::factory()->create();

        $this->asAdmin()
             ->put(route('admin.branches.update', $branch), $this->validBranchData(['name' => 'اسم محدث']))
             ->assertRedirect(route('admin.branches.index'));

        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'name' => 'اسم محدث']);
    }

    public function test_update_branch_allows_same_name(): void
    {
        $branch = Branch::factory()->create(['name' => 'فرع قديم']);

        $this->asAdmin()
             ->put(route('admin.branches.update', $branch), $this->validBranchData(['name' => 'فرع قديم']))
             ->assertRedirect(route('admin.branches.index'));
    }

    public function test_update_branch_requires_valid_geofence_radius(): void
    {
        $branch = Branch::factory()->create();

        $this->asAdmin()
             ->put(route('admin.branches.update', $branch), $this->validBranchData(['geofence_radius' => 0]))
             ->assertSessionHasErrors('geofence_radius');
    }

    // ─── تعطيل فرع (soft deactivate) ────────────────────────────────────────

    public function test_destroy_deactivates_branch(): void
    {
        $branch = Branch::factory()->create(['is_active' => true]);

        $this->asAdmin()
             ->delete(route('admin.branches.destroy', $branch))
             ->assertRedirect(route('admin.branches.index'));

        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'is_active' => false]);
    }

    // ─── Scope active ──────────────────────────────────────────────────────

    public function test_active_scope_returns_only_active_branches(): void
    {
        Branch::factory()->count(3)->create(['is_active' => true]);
        Branch::factory()->count(2)->create(['is_active' => false]);

        $active = Branch::active()->get();
        $this->assertCount(3, $active);
        $this->assertTrue($active->every(fn ($b) => $b->is_active));
    }

    // ─── غير مصادق عليه ────────────────────────────────────────────────────

    public function test_unauthenticated_cannot_access_branch_routes(): void
    {
        $this->get(route('admin.branches.index'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.branches.store'), [])->assertRedirect(route('admin.login'));
    }
}
