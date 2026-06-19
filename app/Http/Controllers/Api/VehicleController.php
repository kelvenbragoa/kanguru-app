<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    /**
     * Display a listing of vehicles
     */
    public function index(Request $request)
    {
        $query = Vehicle::with(['vehicleType', 'vehicleStatus', 'driver']);

        // Filtros opcionais
        if ($request->has('status')) {
            $query->whereHas('vehicleStatus', function ($q) use ($request) {
                $q->where('name', $request->status);
            });
        }

        if ($request->has('type')) {
            $query->whereHas('vehicleType', function ($q) use ($request) {
                $q->where('name', $request->type);
            });
        }

        if ($request->has('available')) {
            $query->available();
        }

        $vehicles = $query->orderBy('license_plate_number')->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $vehicles
        ]);
    }

    /**
     * Store a newly created vehicle
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'license_plate_number' => 'required|string|max:255|unique:vehicles',
            'model' => 'required|string|max:255',
            'color' => 'required|string|max:255',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'driver_id' => 'nullable|exists:users,id',
            'capacity' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $vehicle = Vehicle::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Vehicle created successfully',
            'data' => $vehicle->load(['vehicleType', 'vehicleStatus', 'driver'])
        ], 201);
    }

    /**
     * Display the specified vehicle
     */
    public function show(string $id)
    {
        $vehicle = Vehicle::with([
            'vehicleType', 'vehicleStatus', 'driver', 
            'orders' => function ($query) {
                $query->with(['orderStatus', 'user'])
                      ->whereHas('orderStatus', function ($q) {
                          $q->where('is_final', false);
                      })
                      ->orderBy('scheduled_at');
            }
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $vehicle
        ]);
    }

    /**
     * Update the specified vehicle
     */
    public function update(Request $request, string $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'license_plate_number' => 'sometimes|required|string|max:255|unique:vehicles,license_plate_number,' . $id,
            'model' => 'sometimes|required|string|max:255',
            'color' => 'sometimes|required|string|max:255',
            'vehicle_type_id' => 'sometimes|required|exists:vehicle_types,id',
            'vehicle_status_id' => 'sometimes|required|exists:vehicle_statuses,id',
            'driver_id' => 'nullable|exists:users,id',
            'capacity' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $vehicle->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Vehicle updated successfully',
            'data' => $vehicle->load(['vehicleType', 'vehicleStatus', 'driver'])
        ]);
    }

    /**
     * Remove the specified vehicle
     */
    public function destroy(string $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        // Verificar se o veículo tem pedidos ativos
        $activeOrders = $vehicle->orders()->whereHas('orderStatus', function ($query) {
            $query->where('is_final', false);
        })->count();

        if ($activeOrders > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete vehicle with active orders'
            ], 422);
        }

        $vehicle->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Vehicle deleted successfully'
        ]);
    }

    /**
     * Get available vehicles for assignment
     */
    public function getAvailable(Request $request)
    {
        $query = Vehicle::available()
            ->with(['vehicleType', 'vehicleStatus', 'driver']);

        // Filtrar por capacidade mínima se fornecida
        if ($request->has('min_capacity')) {
            $query->where('capacity', '>=', $request->min_capacity);
        }

        // Filtrar por tipo se fornecido
        if ($request->has('vehicle_type_id')) {
            $query->where('vehicle_type_id', $request->vehicle_type_id);
        }

        $vehicles = $query->orderBy('capacity')->get();

        return response()->json([
            'status' => 'success',
            'data' => $vehicles
        ]);
    }

    /**
     * Assign driver to vehicle
     */
    public function assignDriver(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $vehicle = Vehicle::findOrFail($id);

        $vehicle->update(['driver_id' => $request->driver_id]);

        return response()->json([
            'status' => 'success',
            'message' => 'Driver assigned successfully',
            'data' => $vehicle->load(['driver', 'vehicleType', 'vehicleStatus'])
        ]);
    }
}
