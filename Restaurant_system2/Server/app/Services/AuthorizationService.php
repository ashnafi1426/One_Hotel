<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class AuthorizationService
{
    private const CACHE_TTL_SECONDS = 600;
    protected array $requestPermissionsCache = [];

    public function getUserRoles(User $user, ?string $hotelId = null): Collection
    {
        // 1. Roles assigned via user_roles pivot
        $roles = $user->roles()
            ->where('roles.is_active', true)
            ->get();

        if ($roles->isNotEmpty()) {
            return $roles;
        }

        // 2. Direct role string on user model
        if (!empty($user->role)) {
            $roleModel = Role::where(function ($q) use ($user) {
                $q->where('slug', strtolower($user->role))
                  ->orWhere('name', $user->role);
            })->where('is_active', true)->first();

            if ($roleModel) {
                return collect([$roleModel]);
            }
        }

        // 3. Direct role_id on user model
        if (!empty($user->role_id)) {
            $roleModel = Role::where('id', $user->role_id)->where('is_active', true)->first();
            if ($roleModel) {
                return collect([$roleModel]);
            }
        }

        return collect();
    }

    public function getActiveTemporaryRoles(User $user): Collection
    {
        return collect();
    }

    public function getActiveRoles(User $user, ?string $hotelId = null): Collection
    {
        return $this->getUserRoles($user, $hotelId);
    }

    public function getEffectivePermissions(User $user, ?string $hotelId = null): array
    {
        $runtimeKey = "user_{$user->id}";

        if (isset($this->requestPermissionsCache[$runtimeKey])) {
            return $this->requestPermissionsCache[$runtimeKey];
        }

        $roles = $this->getUserRoles($user);
        $permissionSlugs = [];

        foreach ($roles as $role) {
            $rolePermissions = $role->permissions()
                ->where('permissions.is_active', true)
                ->pluck('permissions.slug')
                ->toArray();

            $permissionSlugs = array_merge($permissionSlugs, $rolePermissions);

            $slug = strtolower($role->slug ?? '');
            if (in_array($slug, ['manager', 'admin', 'receptionist', 'waiter', 'cashier', 'chef'])) {
                if (!in_array('dashboard.view', $permissionSlugs, true)) {
                    $permissionSlugs[] = 'dashboard.view';
                }
            }
        }

        if (method_exists($user, 'directPermissions')) {
            $directPerms = $user->directPermissions()
                ->where('permissions.is_active', true)
                ->pluck('permissions.slug')
                ->toArray();
            $permissionSlugs = array_merge($permissionSlugs, $directPerms);
        }

        $result = array_values(array_unique(array_map('strtolower', $permissionSlugs)));
        $this->requestPermissionsCache[$runtimeKey] = $result;
        return $result;
    }

    public function hasPermission(User $user, string $permissionSlug, ?string $hotelId = null): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $target = strtolower($permissionSlug);
        $effectivePermissions = $this->getEffectivePermissions($user, $hotelId);

        return in_array($target, $effectivePermissions, true);
    }

    public function hasAnyPermission(User $user, array $permissionSlugs, ?string $hotelId = null): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $effectivePermissions = $this->getEffectivePermissions($user, $hotelId);
        foreach ($permissionSlugs as $slug) {
            if (in_array(strtolower($slug), $effectivePermissions, true)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllPermissions(User $user, array $permissionSlugs, ?string $hotelId = null): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $effectivePermissions = $this->getEffectivePermissions($user, $hotelId);
        foreach ($permissionSlugs as $slug) {
            if (!in_array(strtolower($slug), $effectivePermissions, true)) {
                return false;
            }
        }

        return true;
    }

    public function hasRole(User $user, string $roleSlug, ?string $hotelId = null): bool
    {
        $targetSlug = strtolower(trim($roleSlug));
        $activeRoles = $this->getActiveRoles($user, $hotelId);

        foreach ($activeRoles as $r) {
            if (strtolower($r->slug) === $targetSlug || strtolower($r->name) === $targetSlug) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyRole(User $user, array $roleSlugs, ?string $hotelId = null): bool
    {
        foreach ($roleSlugs as $slug) {
            if ($this->hasRole($user, $slug, $hotelId)) {
                return true;
            }
        }
        return false;
    }

    public function invalidateUserCache(string $userId, ?string $hotelId = null): void
    {
        $this->requestPermissionsCache = [];
        Cache::forget("user_permissions_{$userId}");
    }

    public function invalidateRoleCache(Role $role): void
    {
        $this->requestPermissionsCache = [];
        $userIds = DB::table('user_roles')
            ->where('role_id', $role->id)
            ->pluck('user_id')
            ->toArray();

        foreach ($userIds as $userId) {
            $this->invalidateUserCache((string) $userId);
        }
    }
}
