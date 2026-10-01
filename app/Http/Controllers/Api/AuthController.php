<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'phone' => 'nullable|string|max:20',
            'document' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => $request->role_id,
                'is_active' => true,
            ]);

            // Criar perfil se dados fornecidos
            // if ($request->hasAny(['phone', 'document', 'address', 'city', 'state', 'postal_code'])) {
                Profile::create([
                    'user_id' => $user->id,
                    'phone' => $request->phone,
                    'document' => $request->document,
                    'address' => $request->address,
                    'city' => $request->city,
                    'state' => $request->state,
                    'postal_code' => $request->postal_code,
                ]);
            // }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'User registered successfully',
                'data' => [
                    'user' => $user->load(['role', 'profile']),
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->with(['role', 'profile'])->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Account is inactive'
            ], 403);
        }

        $token = $user->createToken('mobile_app')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Authenticated user retrieved successfully',
            'data' => [
                'user' => $request->user()->load(['role', 'profile']),
            ],
        ]);
    }

    /**
     * Alias used by the driver app (`GET /auth/user`).
     */
    public function user(Request $request)
    {
        return $this->me($request);
    }

    /**
     * Update authenticated user profile (name, email and profile fields).
     * Does not allow changing role, status or password.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'document' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'bio' => 'nullable|string|max:500',
            'avatar' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user->update($request->only(['name', 'email']));

            $profileData = $request->only([
                'phone',
                'document',
                'birth_date',
                'address',
                'city',
                'state',
                'postal_code',
                'bio',
                'avatar',
            ]);

            if ($user->profile) {
                $user->profile->update($profileData);
            } else {
                $user->profile()->create($profileData);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => $user->fresh()->load(['role', 'profile']),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profile update failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change password for the authenticated user.
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Current password is incorrect',
                'errors' => [
                    'current_password' => ['Current password is incorrect'],
                ],
            ], 422);
        }

        $user->update([
            'password' => $request->password,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Logout current device (revoke the current Sanctum token).
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        $user->loadMissing('role');
        if ($user->role?->name === 'driver') {
            $user->update(['is_online' => false, 'last_seen_at' => now()]);
        }

        $token = $user->currentAccessToken();

        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $token->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logout successful',
        ]);
    }

    /**
     * Logout from all devices (revoke every Sanctum token).
     */
    public function logoutAll(Request $request)
    {
        $user = $request->user();
        $user->loadMissing('role');
        if ($user->role?->name === 'driver') {
            $user->update(['is_online' => false, 'last_seen_at' => now()]);
        }

        $user->tokens()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out from all devices',
        ]);
    }
}
