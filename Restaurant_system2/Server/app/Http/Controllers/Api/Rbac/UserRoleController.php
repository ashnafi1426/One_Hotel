<?php

namespace App\Http\Controllers\Api\Rbac;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\RbacAuditLog;
use App\Services\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\UserPermission;

class UserRoleController extends Controller
{
    protected AuthorizationService $authService;

    public function __construct(AuthorizationService $authService)
    {
        $this->authService = $authService;
    }

    public function index(Request $request)
    {
        $users = User::with(['temporaryRoleAssignments.role', 'roles'])
            ->orderBy('first_name')
            ->get();

        $userIds = $users->pluck('id')->filter()->toArray();
        $directPermsGrouped = collect();
        if (!empty($userIds)) {
            $directPermsGrouped = UserPermission::whereIn('user_id', $userIds)
                ->get()
                ->groupBy('user_id');
        }

        $data = $users->map(function ($u) use ($directPermsGrouped) {
            $effectivePermissions = $this->authService->getEffectivePermissions($u);
            $activeRoles = $this->authService->getActiveRoles($u);
            $primaryRoleModel = $activeRoles->first();

            $directPermissions = $directPermsGrouped->get($u->id, collect());
            $roleStr = $u->role;

            return [
                'id' => $u->id,
                'full_name' => $u->full_name,
                'email' => $u->email,
                'phone' => $u->phone,
                'legacy_role' => $roleStr,
                'primary_role_name' => $primaryRoleModel ? $primaryRoleModel->name : ucfirst($roleStr ?? 'Staff'),
                'primary_role_slug' => $primaryRoleModel ? $primaryRoleModel->slug : strtolower($roleStr ?? 'staff'),
                'is_active' => $u->is_active,
                'roles' => $activeRoles->map(fn($r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'slug' => $r->slug,
                    'is_primary' => true,
                ]),
                'active_roles' => $activeRoles->pluck('name')->toArray(),
                'temporary_assignments' => [],
                'direct_permissions_count' => $directPermissions->count(),
                'direct_permission_ids' => $directPermissions->pluck('permission_id')->toArray(),
                'effective_permissions_count' => count($effectivePermissions),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function getUserRoles(Request $request, User $user)
    {
        $activeRoles = $this->authService->getActiveRoles($user);
        $effectivePermissions = $this->authService->getEffectivePermissions($user);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'legacy_role' => $user->role,
            ],
            'roles' => $activeRoles,
            'active_roles' => $activeRoles,
            'temporary_assignments' => [],
            'effective_permissions' => $effectivePermissions,
        ]);
    }

    public function assignRoles(Request $request, User $user)
    {
        $currentUser = $request->user();

        $validated = $request->validate([
            'role_ids' => 'required|array|min:1',
            'role_ids.*' => 'exists:roles,id',
            'primary_role_id' => 'nullable|exists:roles,id',
        ]);

        $adminRole = Role::withoutTenant()->where('slug', 'admin')->first();
        if ($adminRole && $user->hasRole('admin') && !in_array($adminRole->id, $validated['role_ids'])) {
            $adminCount = DB::table('user_roles')->where('role_id', $adminRole->id)->count()
                + User::where('role', 'admin')->where('id', '!=', $user->id)->count();
            if ($adminCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove Admin role: Hotel must have at least one active Admin user.',
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $primaryRoleId = $validated['primary_role_id'] ?? $validated['role_ids'][0];
            $primaryRoleModel = Role::withoutTenant()->find($primaryRoleId);

            DB::table('user_roles')->where('user_id', $user->id)->delete();

            $inserts = [];
            foreach ($validated['role_ids'] as $rId) {
                $inserts[] = [
                    'user_id' => $user->id,
                    'role_id' => $rId,
                    'is_primary' => ($rId == $primaryRoleId),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('user_roles')->insert($inserts);

            if ($primaryRoleModel) {
                $user->update(['role' => $primaryRoleModel->slug]);
            }

            $this->authService->invalidateUserCache($user->id);
            Cache::forget('rbac_roles');
            Cache::forget('rbac_active_roles');

            RbacAuditLog::log(
                $currentUser?->id,
                'user.roles_assigned',
                'User',
                (string)$user->id,
                null,
                ['role_ids' => $validated['role_ids'], 'primary_role_id' => $primaryRoleId]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Roles assigned successfully for {$user->full_name}",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign roles: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function removeRole(Request $request, User $user, Role $role)
    {
        if ($role->slug === 'admin') {
            $adminCount = DB::table('user_roles')->where('role_id', $role->id)->count();
            if ($adminCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove Admin role: Hotel must have at least one active Admin user.',
                ], 422);
            }
        }

        DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->delete();

        $this->authService->invalidateUserCache($user->id);
        Cache::forget('rbac_roles');
        Cache::forget('rbac_active_roles');

        RbacAuditLog::log(
            $request->user()?->id,
            'user.role_removed',
            'User',
            (string)$user->id,
            ['removed_role_id' => $role->id],
            null
        );

        return response()->json([
            'success' => true,
            'message' => "Role {$role->name} removed from user {$user->full_name}",
        ]);
    }
}
