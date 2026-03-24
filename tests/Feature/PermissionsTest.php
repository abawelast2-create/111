<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Services\AdminPermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    private Admin $superAdmin;
    private Admin $regularAdmin;
    private PermissionGroup $group;

    protected function setUp(): void
    {
        parent::setUp();
        AdminPermissionService::clearAdminCache();
        \App\Models\Permission::query()->delete();
        \App\Models\PermissionGroup::query()->delete();

        $this->superAdmin    = Admin::factory()->superAdmin()->create();
        $this->regularAdmin  = Admin::factory()->create(['is_super_admin' => false]);

        $this->group = PermissionGroup::firstOrCreate([
            'group_key'   => 'employees_group',
            'name'        => 'إدارة الموظفين',
            'description' => 'اختبار',
        ]);

        Permission::firstOrCreate([
            'permission_group_id' => $this->group->id,
            'permission_key'      => 'employees.view',
            'name'                => 'عرض الموظفين',
        ]);

        Permission::firstOrCreate([
            'permission_group_id' => $this->group->id,
            'permission_key'      => 'employees.create',
            'name'                => 'إضافة موظف',
        ]);
    }

    private function asAdmin(Admin $admin): static
    {
        return $this->withSession([
            'admin_id'      => $admin->id,
            'admin_username'=> $admin->username,
            'admin_name'    => $admin->full_name,
            'last_activity' => time(),
        ]);
    }

    // ─── صفحة الصلاحيات ────────────────────────────────────────────────────

    public function test_permissions_page_accessible_by_super_admin(): void
    {
        $this->asAdmin($this->superAdmin)
             ->get(route('admin.permissions.index'))
             ->assertStatus(200);
    }

    public function test_permissions_page_shows_admins_and_groups(): void
    {
        $this->asAdmin($this->superAdmin)
             ->get(route('admin.permissions.index'))
             ->assertStatus(200)
             ->assertViewHasAll(['admins', 'groups']);
    }

    // ─── Service: adminCan ──────────────────────────────────────────────────

    public function test_super_admin_can_do_anything(): void
    {
        $this->assertTrue(AdminPermissionService::adminCan($this->superAdmin, 'employees.view'));
        $this->assertTrue(AdminPermissionService::adminCan($this->superAdmin, 'nonexistent.permission'));
    }

    public function test_regular_admin_without_groups_cannot_access(): void
    {
        $this->assertFalse(AdminPermissionService::adminCan($this->regularAdmin, 'employees.view'));
    }

    public function test_regular_admin_with_group_can_access_permissions(): void
    {
        $this->regularAdmin->permissionGroups()->attach($this->group->id);
        AdminPermissionService::clearAdminCache($this->regularAdmin->id);

        $this->assertTrue(AdminPermissionService::adminCan($this->regularAdmin, 'employees.view'));
        $this->assertTrue(AdminPermissionService::adminCan($this->regularAdmin, 'employees.create'));
    }

    public function test_regular_admin_cannot_access_permission_not_in_group(): void
    {
        $this->regularAdmin->permissionGroups()->attach($this->group->id);
        AdminPermissionService::clearAdminCache($this->regularAdmin->id);

        $this->assertFalse(AdminPermissionService::adminCan($this->regularAdmin, 'branches.manage'));
    }

    // ─── Middleware: صفحة محمية ─────────────────────────────────────────────

    public function test_super_admin_can_access_protected_route(): void
    {
        $this->asAdmin($this->superAdmin)
             ->get(route('admin.employees.index'))
             ->assertStatus(200);
    }

    public function test_regular_admin_without_permission_gets_403(): void
    {
        $this->asAdmin($this->regularAdmin)
             ->get(route('admin.employees.index'))
             ->assertStatus(403);
    }

    public function test_regular_admin_with_permission_can_access(): void
    {
        $this->regularAdmin->permissionGroups()->attach($this->group->id);
        AdminPermissionService::clearAdminCache($this->regularAdmin->id);

        $this->asAdmin($this->regularAdmin)
             ->get(route('admin.employees.index'))
             ->assertStatus(200);
    }

    // ─── تحديث الصلاحيات ────────────────────────────────────────────────────

    public function test_super_admin_can_assign_groups_to_admin(): void
    {
        $this->asAdmin($this->superAdmin)
             ->post(route('admin.permissions.assign', $this->regularAdmin), [
                 'permission_group_ids' => [$this->group->id],
             ])->assertRedirect();

        $this->assertTrue($this->regularAdmin->permissionGroups()->where('permission_groups.id', $this->group->id)->exists());
    }

    public function test_cannot_remove_super_admin_from_last_super_admin(): void
    {
        // superAdmin هو الوحيد ذو is_super_admin=true
        $response = $this->asAdmin($this->superAdmin)
                         ->post(route('admin.permissions.assign', $this->superAdmin), [
                             'is_super_admin'       => false,
                             'permission_group_ids' => [],
                         ]);

        $response->assertRedirect();
        $this->superAdmin->refresh();
        $this->assertTrue($this->superAdmin->is_super_admin);
    }

    public function test_permission_keys_for_admin_returns_correct_keys(): void
    {
        $this->regularAdmin->permissionGroups()->attach($this->group->id);
        AdminPermissionService::clearAdminCache($this->regularAdmin->id);

        $keys = AdminPermissionService::permissionKeysForAdmin($this->regularAdmin);

        $this->assertContains('employees.view', $keys);
        $this->assertContains('employees.create', $keys);
        $this->assertNotContains('branches.manage', $keys);
    }

    // ─── كاش الصلاحيات ──────────────────────────────────────────────────────

    public function test_permission_cache_cleared_after_update(): void
    {
        $this->regularAdmin->permissionGroups()->attach($this->group->id);
        AdminPermissionService::clearAdminCache($this->regularAdmin->id);

        $keys = AdminPermissionService::permissionKeysForAdmin($this->regularAdmin);
        $this->assertContains('employees.view', $keys);

        // إزالة المجموعة + مسح الكاش
        $this->regularAdmin->permissionGroups()->detach($this->group->id);
        AdminPermissionService::clearAdminCache($this->regularAdmin->id);

        $keys = AdminPermissionService::permissionKeysForAdmin($this->regularAdmin);
        $this->assertNotContains('employees.view', $keys);
    }
}


