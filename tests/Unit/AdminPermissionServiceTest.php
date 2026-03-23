<?php

namespace Tests\Unit;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Services\AdminPermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private Admin $superAdmin;
    private Admin $regularAdmin;
    private PermissionGroup $groupA;
    private PermissionGroup $groupB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin   = Admin::factory()->superAdmin()->create();
        $this->regularAdmin = Admin::factory()->create(['is_super_admin' => false]);

        $this->groupA = PermissionGroup::create([
            'group_key' => 'group_a',
            'name'      => 'Group A',
        ]);

        $this->groupB = PermissionGroup::create([
            'group_key' => 'group_b',
            'name'      => 'Group B',
        ]);

        Permission::create([
            'permission_group_id' => $this->groupA->id,
            'permission_key'      => 'employees.view',
            'name'                => 'عرض الموظفين',
        ]);

        Permission::create([
            'permission_group_id' => $this->groupA->id,
            'permission_key'      => 'employees.create',
            'name'                => 'إضافة موظف',
        ]);

        Permission::create([
            'permission_group_id' => $this->groupB->id,
            'permission_key'      => 'branches.view',
            'name'                => 'عرض الفروع',
        ]);
    }

    // ─── adminCan ──────────────────────────────────────────────────────────

    public function test_super_admin_can_any_permission(): void
    {
        $this->assertTrue(AdminPermissionService::adminCan($this->superAdmin, 'employees.view'));
        $this->assertTrue(AdminPermissionService::adminCan($this->superAdmin, 'anything.random'));
    }

    public function test_regular_admin_without_any_group_cannot(): void
    {
        $this->assertFalse(AdminPermissionService::adminCan($this->regularAdmin, 'employees.view'));
    }

    public function test_regular_admin_with_group_can_its_permissions(): void
    {
        $this->regularAdmin->permissionGroups()->attach($this->groupA->id);
        AdminPermissionService::clearAdminCache($this->regularAdmin->id);

        $this->assertTrue(AdminPermissionService::adminCan($this->regularAdmin, 'employees.view'));
        $this->assertTrue(AdminPermissionService::adminCan($this->regularAdmin, 'employees.create'));
    }

    public function test_regular_admin_cannot_cross_group_permissions(): void
    {
        $this->regularAdmin->permissionGroups()->attach($this->groupA->id);
        AdminPermissionService::clearAdminCache($this->regularAdmin->id);

        $this->assertFalse(AdminPermissionService::adminCan($this->regularAdmin, 'branches.view'));
    }

    public function test_adding_second_group_grants_its_permissions(): void
    {
        $this->regularAdmin->permissionGroups()->attach([$this->groupA->id, $this->groupB->id]);
        AdminPermissionService::clearAdminCache($this->regularAdmin->id);

        $this->assertTrue(AdminPermissionService::adminCan($this->regularAdmin, 'employees.view'));
        $this->assertTrue(AdminPermissionService::adminCan($this->regularAdmin, 'branches.view'));
    }

    // ─── permissionKeysForAdmin ────────────────────────────────────────────

    public function test_permission_keys_returns_all_keys_in_group(): void
    {
        $this->regularAdmin->permissionGroups()->attach($this->groupA->id);
        AdminPermissionService::clearAdminCache($this->regularAdmin->id);

        $keys = AdminPermissionService::permissionKeysForAdmin($this->regularAdmin);

        $this->assertContains('employees.view', $keys);
        $this->assertContains('employees.create', $keys);
        $this->assertNotContains('branches.view', $keys);
    }

    public function test_permission_keys_empty_for_admin_with_no_groups(): void
    {
        $keys = AdminPermissionService::permissionKeysForAdmin($this->regularAdmin);
        $this->assertEmpty($keys);
    }

    public function test_permission_keys_returns_unique_keys(): void
    {
        // إرفاق نفس المجموعة مرتين (لا يجب تكرار المفاتيح)
        $this->regularAdmin->permissionGroups()->attach($this->groupA->id);
        AdminPermissionService::clearAdminCache($this->regularAdmin->id);

        $keys = AdminPermissionService::permissionKeysForAdmin($this->regularAdmin);

        $this->assertEquals(count($keys), count(array_unique($keys)));
    }

    // ─── كاش ──────────────────────────────────────────────────────────────

    public function test_cache_is_set_after_first_call(): void
    {
        $this->regularAdmin->permissionGroups()->attach($this->groupA->id);
        AdminPermissionService::clearAdminCache($this->regularAdmin->id);

        AdminPermissionService::permissionKeysForAdmin($this->regularAdmin);

        // استدعاء ثانٍ يجب أن يستخدم الكاش (لا يرمي استثناء)
        $keys = AdminPermissionService::permissionKeysForAdmin($this->regularAdmin);
        $this->assertContains('employees.view', $keys);
    }

    public function test_clear_cache_refreshes_permissions(): void
    {
        $this->regularAdmin->permissionGroups()->attach($this->groupA->id);
        AdminPermissionService::clearAdminCache($this->regularAdmin->id);

        $keysBeforeDetach = AdminPermissionService::permissionKeysForAdmin($this->regularAdmin);
        $this->assertContains('employees.view', $keysBeforeDetach);

        $this->regularAdmin->permissionGroups()->detach($this->groupA->id);
        AdminPermissionService::clearAdminCache($this->regularAdmin->id);

        $keysAfterDetach = AdminPermissionService::permissionKeysForAdmin($this->regularAdmin);
        $this->assertNotContains('employees.view', $keysAfterDetach);
    }

    // ─── validateDependenciesForGroups ────────────────────────────────────

    public function test_no_missing_deps_when_all_satisfied(): void
    {
        $missing = AdminPermissionService::validateDependenciesForGroups([$this->groupA->id]);
        $this->assertEmpty($missing);
    }

    public function test_missing_deps_detected(): void
    {
        // أضف permission تعتمد على شيء آخر غير موجود في المجموعة
        Permission::create([
            'permission_group_id' => $this->groupA->id,
            'permission_key'      => 'employees.delete',
            'name'                => 'حذف موظف',
            'depends_on'          => ['superpower.required'],
        ]);

        $missing = AdminPermissionService::validateDependenciesForGroups([$this->groupA->id]);

        $this->assertNotEmpty($missing);
        $this->assertEquals('employees.delete', $missing[0]['permission']);
        $this->assertEquals('superpower.required', $missing[0]['required']);
    }

    // ─── currentAdmin ────────────────────────────────────────────────────

    public function test_current_admin_returns_null_without_session(): void
    {
        $this->assertNull(AdminPermissionService::currentAdmin());
    }

    public function test_current_admin_returns_admin_from_session(): void
    {
        $this->withSession(['admin_id' => $this->regularAdmin->id]);
        session(['admin_id' => $this->regularAdmin->id]);

        $admin = AdminPermissionService::currentAdmin();
        $this->assertNotNull($admin);
        $this->assertEquals($this->regularAdmin->id, $admin->id);
    }
}
