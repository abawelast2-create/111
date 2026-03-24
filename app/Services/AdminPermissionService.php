<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\PermissionGroup;

class AdminPermissionService
{
    private static array $permissionCache = [];

    public static function currentAdmin(): ?Admin
    {
        $adminId = session('admin_id');
        if (!$adminId) {
            return null;
        }

        return Admin::find($adminId);
    }

    public static function currentCan(string $permissionKey): bool
    {
        $admin = self::currentAdmin();

        return $admin ? self::adminCan($admin, $permissionKey) : false;
    }

    public static function adminCan(Admin $admin, string $permissionKey): bool
    {
        if ($admin->is_super_admin) {
            return true;
        }

        $keys = self::permissionKeysForAdmin($admin);

        return in_array($permissionKey, $keys, true);
    }

    public static function permissionKeysForAdmin(Admin $admin): array
    {
        if (isset(self::$permissionCache[$admin->id])) {
            return self::$permissionCache[$admin->id];
        }

        $keys = Permission::query()
            ->whereHas('group.admins', function ($q) use ($admin) {
                $q->where('admins.id', $admin->id);
            })
            ->pluck('permission_key')
            ->values()
            ->all();

        self::$permissionCache[$admin->id] = $keys;

        return $keys;
    }

    public static function clearAdminCache(?int $adminId = null): void
    {
        if ($adminId !== null) {
            unset(self::$permissionCache[$adminId]);
        } else {
            self::$permissionCache = [];
        }
    }

    public static function validateDependenciesForGroups(array $groupIds): array
    {
        $groups = PermissionGroup::query()
            ->with('permissions:id,permission_group_id,permission_key,depends_on')
            ->whereIn('id', $groupIds)
            ->get();

        $granted = [];
        foreach ($groups as $group) {
            foreach ($group->permissions as $permission) {
                $granted[] = $permission->permission_key;
            }
        }

        $granted = array_values(array_unique($granted));
        $grantedLookup = array_fill_keys($granted, true);

        $missing = [];
        foreach ($groups as $group) {
            foreach ($group->permissions as $permission) {
                foreach (($permission->depends_on ?? []) as $required) {
                    if (!isset($grantedLookup[$required])) {
                        $missing[] = [
                            'permission' => $permission->permission_key,
                            'required' => $required,
                        ];
                    }
                }
            }
        }

        return $missing;
    }
}
