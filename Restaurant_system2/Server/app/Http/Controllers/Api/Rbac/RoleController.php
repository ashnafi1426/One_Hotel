<?php

namespace App\Http\Controllers\Api\Rbac;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Models\Hotel;
use App\Models\RbacAuditLog;
use App\Services\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RoleController extends Controller
{
    protected AuthorizationService $authService;

    public function __construct(AuthorizationService $authService)
    {
        $this->authService = $authService;
    }

    protected function invalidateRoleCaches(): void
    {
        Cache::forget('rbac_roles');
        Cache::forget('rbac_active_roles');
    }

    public function index(Request $request)
    {
        $cacheKey = 'rbac_roles';

        if ($request->boolean('refresh') || $request->header('X-Refresh') === 'true') {
            Cache::forget($cacheKey);
        }

        $rolesData = Cache::remember($cacheKey, 600, function () {
            $roles = Role::withoutTenant()
                ->select([
                    'id',
                    'name',
                    'display_name',
                    'slug',
                    'description',
                    'is_system',
                    'is_active',
                    'created_at',
                    'updated_at',
                ])
                ->with(['permissions:id,name,slug,module,action'])
                ->withCount(['permissions'])
                ->orderBy('name')
                ->get();

            if ($roles->isNotEmpty()) {
                $roleIds = $roles->pluck('id')->all();
                $userRoleCounts = DB::table('user_roles')
                    ->whereIn('role_id', $roleIds)
                    ->select('role_id', DB::raw('count(*) as aggregate'))
                    ->groupBy('role_id')
                    ->pluck('aggregate', 'role_id');

                $roles->transform(function ($role) use ($userRoleCounts) {
                    $role->users_count = (int) ($userRoleCounts[$role->id] ?? 0);
                    return $role;
                });
            }

            return $roles->toArray();
        });

        return response()->json([
            'success' => true,
            'data' => $rolesData,
        ]);
    }

    public function getActiveRoles(Request $request)
    {
        $cacheKey = 'rbac_active_roles';

        if ($request->boolean('refresh') || $request->header('X-Refresh') === 'true') {
            Cache::forget($cacheKey);
        }

        $rolesData = Cache::remember($cacheKey, 600, function () {
            return Role::withoutTenant()
                ->select([
                    'id',
                    'name',
                    'display_name',
                    'slug',
                    'description',
                    'is_system',
                    'is_active',
                    'created_at',
                    'updated_at',
                ])
                ->where('is_active', true)
                ->with(['permissions:id,name,slug,module,action'])
                ->withCount(['permissions'])
                ->orderBy('name')
                ->get()
                ->toArray();
        });

        return response()->json([
            'success' => true,
            'data' => $rolesData,
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $slug = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        if (Role::withoutTenant()->where('slug', $slug)->exists()) {
            return response()->json([
                'success' => false,
                'message' => "A role with slug '{$slug}' already exists.",
            ], 422);
        }

        DB::beginTransaction();
        try {
            $role = Role::withoutTenant()->create([
                'name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'is_system' => false,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            if (!empty($validated['permissions'])) {
                $role->permissions()->sync($validated['permissions']);
            }

            RbacAuditLog::log(
                $request->user()?->id,
                'role.created',
                'Role',
                (string)$role->id,
                null,
                $role->toArray()
            );

            DB::commit();

            $this->invalidateRoleCaches();

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully',
                'data' => $role->load(['permissions']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create role: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified role.
     */
    public function show(Request $request, Role $role)
    {
        $role->load(['permissions']);

        return response()->json([
            'success' => true,
            'data' => $role,
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|required|boolean',
        ]);

        if ($role->slug === 'admin' && isset($validated['is_active']) && !$validated['is_active']) {
            return response()->json([
                'success' => false,
                'message' => 'The Admin role cannot be deactivated.',
            ], 422);
        }

        $updateData = [];
        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }
        if (array_key_exists('description', $validated)) {
            $updateData['description'] = $validated['description'];
        }
        if (isset($validated['is_active'])) {
            $updateData['is_active'] = $validated['is_active'];
        }

        $oldValues = $role->toArray();
        $role->update($updateData);

        $this->authService->invalidateRoleCache($role);
        $this->invalidateRoleCaches();

        RbacAuditLog::log(
            $request->user()?->id,
            'role.updated',
            'Role',
            (string)$role->id,
            $oldValues,
            $role->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully',
            'data' => $role->load(['permissions']),
        ]);
    }

    public function destroy(Request $request, Role $role)
    {
        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'System roles cannot be deleted. You may deactivate custom roles instead.',
            ], 422);
        }

        $assignedCount = DB::table('user_roles')->where('role_id', $role->id)->count();

        if ($assignedCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete role because users are currently assigned to it.',
            ], 422);
        }

        $oldValues = $role->toArray();
        $this->authService->invalidateRoleCache($role);

        $role->delete();

        $this->invalidateRoleCaches();

        RbacAuditLog::log(
            $request->user()?->id,
            'role.deleted',
            'Role',
            (string)$role->id,
            $oldValues,
            null
        );

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully',
        ]);
    }

    /**
     * Get all permission IDs assigned to a role. Cached for swift modal display.
     */
    public function getPermissions(Request $request, Role $role)
    {
        $cacheKey = "rbac_role_perms:{$role->id}";
        if ($request->boolean('refresh')) {
            Cache::forget($cacheKey);
        }

        $data = Cache::remember($cacheKey, 600, function () use ($role) {
            $permissions = $role->permissions()
                ->select([
                    'permissions.id',
                    'permissions.name',
                    'permissions.slug',
                    'permissions.module',
                    'permissions.action',
                    'permissions.description',
                    'permissions.is_active',
                ])
                ->get();

            return [
                'data' => $permissions->toArray(),
                'permission_ids' => $permissions->pluck('id')->all(),
            ];
        });

        return response()->json([
            'success' => true,
            'role_id' => $role->id,
            'data' => $data['data'],
            'permission_ids' => $data['permission_ids'],
        ]);
    }

    public function syncPermissions(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permission_ids' => 'present|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $oldPermissionIds = $role->permissions()->pluck('permissions.id')->toArray();

        $role->permissions()->sync($validated['permission_ids']);

        $this->authService->invalidateRoleCache($role);
        Cache::forget("rbac_role_perms:{$role->id}");
        $this->invalidateRoleCaches();

        RbacAuditLog::log(
            $request->user()?->id,
            'role.permissions_updated',
            'Role',
            (string)$role->id,
            ['permission_ids' => $oldPermissionIds],
            ['permission_ids' => $validated['permission_ids']]
        );

        return response()->json([
            'success' => true,
            'message' => "Permissions updated successfully for {$role->name}",
            'role_id' => $role->id,
            'data' => $role->load('permissions'),
        ]);
    }
}
