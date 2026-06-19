<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $users = User::with(['profile', 'role'])->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $users
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'phone' => 'nullable|string|max:20',
            'document' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:2',
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


            return response()->json([
                'status' => 'success',
                'message' => 'User registered successfully',
                'data' => [
                    'user' => $user->load(['role', 'profile'])
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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $user = User::with(['role', 'profile'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $user
        ]);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'role_id' => 'sometimes|required|exists:roles,id',
            'is_active' => 'sometimes|boolean',
            'phone' => 'nullable|string|max:20',
            'document' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:2',
            'postal_code' => 'nullable|string|max:10',
            'bio' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Atualizar dados do usuário
            $userData = $request->only(['name', 'email', 'role_id', 'is_active']);
            
            // Criptografar senha se fornecida
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            $user->update($userData);

            // Atualizar ou criar perfil
            $profileData = $request->only([
                'phone', 'document', 'birth_date', 'address', 
                'city', 'state', 'postal_code', 'bio'
            ]);

            if ($user->profile) {
                $user->profile->update($profileData);
            } else {
                $user->profile()->create(array_merge($profileData, ['user_id' => $user->id]));
            }

            return response()->json([
                'status' => 'success',
                'message' => 'User updated successfully',
                'data' => $user->load(['role', 'profile'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $user = User::findOrFail($id);

            // Verificar se o usuário tem pedidos ativos
            $activeOrders = $user->orders()->whereHas('orderStatus', function ($query) {
                $query->where('is_final', false);
            })->count();

            if ($activeOrders > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete user with active orders'
                ], 422);
            }

            // Verificar se é um motorista com veículos designados
            $assignedVehicles = $user->vehicles()->count();
            if ($assignedVehicles > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete driver with assigned vehicles. Please reassign vehicles first.'
                ], 422);
            }

            // Soft delete - marcar como inativo ao invés de deletar
            $user->update(['is_active' => false]);

            // Ou delete físico se preferir:
            // $user->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'User deactivated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Delete failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activate/Deactivate user
     */
    public function toggleStatus(string $id)
    {
        try {
            $user = User::findOrFail($id);
            
            $user->update(['is_active' => !$user->is_active]);
            
            $status = $user->is_active ? 'activated' : 'deactivated';
            
            return response()->json([
                'status' => 'success',
                'message' => "User {$status} successfully",
                'data' => $user->load(['role', 'profile'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Status update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get users by role
     */
    public function getUsersByRole(string $roleName)
    {
        try {
            $users = User::whereHas('role', function ($query) use ($roleName) {
                $query->where('name', $roleName);
            })
            ->with(['role', 'profile'])
            ->where('is_active', true)
            ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Users retrieved successfully',
                'data' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available drivers
     */
    public function getAvailableDrivers()
    {
        try {
            $drivers = User::whereHas('role', function ($query) {
                $query->where('name', 'driver');
            })
            ->with(['role', 'profile', 'vehicles'])
            ->where('is_active', true)
            ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Available drivers retrieved successfully',
                'data' => $drivers
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve drivers',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
