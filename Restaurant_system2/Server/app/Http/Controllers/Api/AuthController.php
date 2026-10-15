<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Authrequest;
use App\Http\Resources\AuthResource;
use App\Models\Hotel;
use App\Models\User;
use App\Services\AuthorizationService;
use App\Services\TenantContext;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Authenticate user, issue Sanctum token, and return hotel context.
     */
    public function login(Authrequest $request): JsonResponse
    {
        Log::info('Login attempt', [
            'email' => $request->email,
            'ip' => $request->ip(),
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                Log::warning('Login failed: User not found', ['email' => $request->email]);

                return response()->json([
                    'success' => false,
                    'message' => trans_msg('login_failed', default: 'Invalid credentials'),
                ], 401);
            }

            if ($user->needsActivation() && empty($user->password_hash)) {
                return response()->json([
                    'success' => false,
                    'needs_activation' => true,
                    'message' => trans_msg('account_not_activated', default: 'Account not activated. Please check your email for the activation link.'),
                ], 403);
            }

            if (!Hash::check($request->password, $user->password_hash)) {
                return response()->json([
                    'success' => false,
                    'message' => trans_msg('login_failed', default: 'Invalid credentials'),
                ], 401);
            }

            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => trans_msg('account_disabled', default: 'Account disabled'),
                ], 403);
            }

            $user->update([
                'last_login' => now(),
                'activation_status' => 'activated',
            ]);

            $token = $user->createToken('hotel_token')->plainTextToken;
            $hotel = Hotel::first();
            $hotelPayload = $hotel ? $this->formatHotelSummary($hotel, $user) : null;

            return response()->json([
                'success' => true,
                'message' => trans_msg('login_success', default: 'Login successful'),
                'token' => $token,
                'user' => new AuthResource($user),
                'hotels' => $hotelPayload ? [$hotelPayload] : [],
                'current_hotel' => $hotelPayload,
            ]);
        } catch (Exception $exception) {
            Log::error('Login exception', [
                'email' => $request->email,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Login error. Please try again.',
            ], 500);
        }
    }

    /**
     * Get authenticated user profile and hotel state.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $hotel = Hotel::first();
        $hotelPayload = $hotel ? $this->formatHotelSummary($hotel, $user) : null;

        return response()->json([
            'success' => true,
            'user' => new AuthResource($user),
            'current_hotel' => $hotelPayload,
            'hotels' => $hotelPayload ? [$hotelPayload] : [],
        ]);
    }

    /**
     * Return single hotel workspace.
     */
    public function switchHotel(Request $request): JsonResponse
    {
        $user = $request->user();
        $hotel = Hotel::first();
        $hotelPayload = $hotel ? $this->formatHotelSummary($hotel, $user) : null;

        $authService = app(AuthorizationService::class);
        $activeRoles = $authService->getActiveRoles($user);

        return response()->json([
            'success' => true,
            'message' => 'Active hotel context',
            'user' => new AuthResource($user),
            'current_hotel' => $hotelPayload,
            'roles' => $activeRoles->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'slug' => $r->slug,
            ])->values()->toArray(),
            'permissions' => $authService->getEffectivePermissions($user),
        ]);
    }

    /**
     * Return single hotel for the user.
     */
    public function myHotels(Request $request): JsonResponse
    {
        $hotel = Hotel::first();
        $payload = $hotel ? [$this->formatHotelSummary($hotel, $request->user())] : [];

        return response()->json([
            'success' => true,
            'hotels' => $payload,
        ]);
    }

    /**
     * Log out current user and revoke token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => trans_msg('logout_success', default: 'Logged out successfully'),
        ]);
    }

    /**
     * Update authenticated user's password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password does not match.',
            ], 422);
        }

        $user->update([
            'password_hash' => Hash::make($validated['new_password']),
            'must_change_password' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }

    private function formatHotelSummary(Hotel $hotel, User $user): array
    {
        return [
            'id' => $hotel->id,
            'name' => $hotel->name,
            'slug' => $hotel->slug,
            'code' => $hotel->code ?? 'HOTEL',
            'logo' => $hotel->logo,
            'address' => $hotel->address,
            'city' => $hotel->city,
            'country' => $hotel->country,
            'currency' => $hotel->currency ?? 'ETB',
            'timezone' => $hotel->timezone ?? 'Africa/Addis_Ababa',
            'phone' => $hotel->phone,
            'email' => $hotel->email,
            'role' => $user->role ?? 'staff',
        ];
    }
}
