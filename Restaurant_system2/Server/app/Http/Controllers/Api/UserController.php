<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Mail\NewUserCreated;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class UserController extends Controller
{
    /**
     * Invalidate cached user listings.
     */
    protected function invalidateUserCaches(?string $hotelId = null): void
    {
        Cache::forget('rbac_roles');
        Cache::forget('rbac_active_roles');
        $cur = (int) Cache::get('users_ver', 1);
        Cache::put('users_ver', $cur + 1, 86400);
    }

    /**
     * Display a listing of users for the single hotel.
     */
    public function index(Request $request): JsonResponse
    {
        $forceRefresh = $request->boolean('refresh') || $request->header('X-Refresh') === 'true';

        $page = (int) $request->input('page', 1);
        $perPage = min((int) $request->input('per_page', 15), 100);
        $search = trim((string) $request->input('search', ''));
        $role = trim((string) $request->input('role', ''));
        $status = $request->input('status');
        $ver = (int) Cache::get('users_ver', 1);

        $cacheKey = 'users_list:' . md5(json_encode([$ver, $page, $perPage, $search, $role, $status]));

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        try {
            $result = Cache::remember($cacheKey, 120, function () use ($request, $perPage) {
                $query = User::select([
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'phone',
                    'role',
                    'is_active',
                    'last_login',
                    'created_at',
                    'updated_at',
                ]);

                if ($request->filled('search')) {
                    $search = trim($request->search);
                    $query->where(function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('role', 'like', "%{$search}%");
                    });
                }

                if ($request->filled('role')) {
                    $query->where('role', $request->role);
                }

                if ($request->has('status') && $request->status !== null && $request->status !== '') {
                    $isActive = filter_var($request->status, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($isActive !== null) {
                        $query->where('is_active', $isActive);
                    }
                }

                $sortBy = in_array($request->input('sort_by'), ['created_at', 'first_name', 'email', 'role'])
                    ? $request->input('sort_by')
                    : 'created_at';
                $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

                return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
            });
        } catch (\Throwable $cacheErr) {
            // Cache table may not exist (e.g. fresh Render deploy) — run query directly
            Log::warning('[UserController] Cache failed, running direct query: ' . $cacheErr->getMessage());
            $query = User::select(['id','first_name','last_name','email','phone','role','is_active','last_login','created_at','updated_at']);
            if ($request->filled('search')) {
                $s = trim($request->search);
                $query->where(fn($q) => $q->where('first_name','like',"%{$s}%")->orWhere('last_name','like',"%{$s}%")->orWhere('email','like',"%{$s}%")->orWhere('role','like',"%{$s}%"));
            }
            if ($request->filled('role')) { $query->where('role', $request->role); }
            $result = $query->orderBy('created_at','desc')->paginate($perPage);
        }

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($result),
            'meta' => [
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
            ],
            'cached' => !$forceRefresh,
        ]);
    }

    /**
     * Store a newly created user in storage.
     * 
     * FIXES APPLIED (2024):
     * - Generates plaintext password BEFORE hashing, ensures it's available for email
     * - Logs user creation with timestamp and email
     * - Checks mail configuration BEFORE sending to detect log-only mode in production
     * - Captures mail sending status/errors and includes in response
     * - Frontend can now show admin whether email was sent or failed
     * - Added detailed logging for troubleshooting deployed systems
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $plainPassword = $request->password ?: Str::random(12);

            // Log user creation before persisting
            Log::info('[UserController.store] Creating user', [
                'email' => $request->email,
                'role' => $request->role,
                'timestamp' => now()->toIso8601String(),
                'request_all' => $request->all(), // Log full request for debugging
            ]);

            $user = User::create([
                'first_name'        => $request->first_name,
                'last_name'         => $request->last_name,
                'email'             => $request->email,
                'phone'             => $request->phone,
                'password_hash'     => Hash::make($plainPassword),  // MUST use password_hash — that's the actual DB column
                'role'              => $request->role,
                'is_active'         => $request->boolean('is_active', true),
                'activation_status' => 'activated',
                'email_verified_at' => now(),
            ]);

            if (!empty($user->role)) {
                $roleSearch = strtolower(trim($user->role));
                $targetRole = Role::withoutTenant()
                    ->where(function ($q) use ($roleSearch) {
                        $q->whereRaw('LOWER(slug) = ?', [$roleSearch])
                          ->orWhereRaw('LOWER(name) = ?', [$roleSearch]);
                    })
                    ->first();

                if ($targetRole) {
                    DB::table('user_roles')->updateOrInsert(
                        [
                            'user_id' => $user->id,
                            'role_id' => $targetRole->id,
                        ],
                        [
                            'is_primary' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }

                if (in_array(strtolower($user->role ?? ''), ['waiter'])) {
                    \App\Models\Waiter::firstOrCreate(
                        ['user_id' => $user->id],
                        [
                            'section' => 'All Sections',
                            'shift' => 'morning',
                            'experience_level' => 'junior',
                            'status' => 'active',
                            'availability' => 'available',
                            'maximum_orders' => 5,
                        ]
                    );
                }
            }

            DB::commit();

            $this->invalidateUserCaches();

            // Check mail configuration before sending
            $currentMailer = config('mail.default');
            $appEnv = config('app.env', 'local');
            $mailWarning = null;
            
            // Diagnostic logging for mail configuration
            $diagnosticData = [
                'mail_mailer_env' => env('MAIL_MAILER', 'NOT SET'),
                'mail_mailer_config' => $currentMailer,
                'app_env' => $appEnv,
                'mail_host' => config('mail.mailers.smtp.host', 'not configured'),
                'mail_port' => config('mail.mailers.smtp.port', 'not configured'),
                'mail_username_partial' => substr((string)config('mail.mailers.smtp.username', ''), 0, 5) . '****',
                'timestamp' => now()->toIso8601String(),
            ];
            
            Log::info('[UserController.store] Mail configuration diagnostic', $diagnosticData);
            
            // Also output to stderr for Render visibility
            error_log('[UserController.store] Mail config: ' . json_encode($diagnosticData));

            if ($currentMailer === 'log' && $appEnv !== 'local') {
                $mailWarning = "Mail mailer is set to LOG mode (for development) but APP_ENV is '{$appEnv}'. Emails will NOT be sent to users!";
                Log::warning('[UserController.store] CRITICAL: Mail configuration issue', [
                    'warning' => $mailWarning,
                    'mail_mailer' => $currentMailer,
                    'app_env' => $appEnv,
                ]);
            }

            // Send welcome email — log all outcomes so admin can debug if needed
            $mailStatus = 'sent';
            $mailError  = null;
            
            Log::info('[UserController.store] Attempting to send welcome email', [
                'user_email' => $user->email,
                'mail_mailer' => $currentMailer,
                'mail_host' => config('mail.mailers.smtp.host', 'not configured'),
            ]);

            try {
                // ⚠️ DEBUG: Test SMTP connection before sending
                $mailer = config('mail.mailers.smtp');
                \Log::warning('[UserController.store] DEBUG - About to send email with mailer config', [
                    'mailer_type' => $currentMailer,
                    'smtp_host' => $mailer['host'] ?? 'NOT SET',
                    'smtp_port' => $mailer['port'] ?? 'NOT SET',
                    'smtp_username_length' => strlen((string)($mailer['username'] ?? '')),
                    'smtp_password_length' => strlen((string)($mailer['password'] ?? '')),
                    'recipient_email' => $user->email,
                ]);
                
                Mail::to($user->email)->send(new NewUserCreated($user, $plainPassword));
                Log::info('[UserController.store] Welcome email sent successfully', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'mailer' => $currentMailer,
                    'timestamp' => now()->toIso8601String(),
                ]);
                $mailStatus = 'sent';
            } catch (Throwable $mailException) {
                $mailStatus = 'failed';
                $mailError  = $mailException->getMessage();
                Log::error('[UserController.store] Welcome email failed to send', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'mailer' => $currentMailer,
                    'error' => $mailException->getMessage(),
                    'exception_class' => get_class($mailException),
                    'error_code' => $mailException->getCode(),
                    'trace_snippet' => substr($mailException->getTraceAsString(), 0, 500),
                ]);
            }

            return response()->json([
                'success'      => true,
                'message'      => 'User created successfully. Login credentials have been sent to ' . $user->email,
                'data'         => new UserResource($user),
                'mail_status'  => $mailStatus,      // 'sent' or 'failed'
                'mail_error'   => $mailError,       // null on success, error message on failure
                'mail_warning' => $mailWarning,     // null normally, warning message if mailer is 'log' in production
                'password_generated' => !$request->filled('password'),  // Indicates if password was auto-generated
            ], 201);
        } catch (Throwable $exception) {
            DB::rollBack();

            Log::error('[UserController.store] Failed to create user', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'email' => $request->email,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to create user: ' . $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ], 200);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        DB::beginTransaction();
        try {
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->phone = $request->phone;

            if ($request->has('role')) {
                $user->role = $request->role;
            }

            if ($request->has('is_active')) {
                $user->is_active = $request->boolean('is_active');
            }

            if ($request->filled('password')) {
                $user->password_hash = Hash::make($request->password);
            }

            $user->save();

            if ($request->has('role')) {
                $roleSearch = strtolower(trim((string) $request->role));
                $roleModel = Role::withoutTenant()
                    ->where(function ($q) use ($roleSearch) {
                        $q->whereRaw('LOWER(slug) = ?', [$roleSearch])
                          ->orWhereRaw('LOWER(name) = ?', [$roleSearch]);
                    })
                    ->first();

                if ($roleModel) {
                    $user->roles()->sync([
                        $roleModel->id => ['is_primary' => true],
                    ]);
                }
            }

            DB::commit();

            $this->invalidateUserCaches();

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.',
                'data' => new UserResource($user),
            ], 200);
        } catch (Throwable $exception) {
            DB::rollBack();

            Log::error('Failed to update user', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to update user.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        if (auth('sanctum')->id() === $user->id || auth()->id() === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ], 403);
        }

        DB::beginTransaction();
        try {
            $user->delete();
            DB::commit();

            $this->invalidateUserCaches();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.',
            ], 200);
        } catch (Throwable $exception) {
            DB::rollBack();

            Log::error('Failed to delete user', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete user.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle the active status of the specified user.
     */
    public function toggleStatus(User $user): JsonResponse
    {
        DB::beginTransaction();
        try {
            $user->is_active = !$user->is_active;
            $user->save();

            DB::commit();

            $this->invalidateUserCaches();

            return response()->json([
                'success' => true,
                'message' => $user->is_active
                    ? 'User activated successfully.'
                    : 'User deactivated successfully.',
                'data' => new UserResource($user),
            ], 200);
        } catch (Throwable $exception) {
            DB::rollBack();

            Log::error('Failed to toggle user status', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to toggle user status.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(string $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $user = User::onlyTrashed()->findOrFail($id);
            $user->restore();

            DB::commit();

            $this->invalidateUserCaches();

            return response()->json([
                'success' => true,
                'message' => 'User restored successfully.',
                'data' => new UserResource($user),
            ], 200);
        } catch (Throwable $exception) {
            DB::rollBack();

            Log::error('Failed to restore user', [
                'user_id' => $id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to restore user.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }
}
