<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Scooter;
use App\Models\ScooterRental;
use App\Models\ScooterSetting;
use App\Models\ScooterStation;
use Illuminate\Http\Request;

class ScooterAdminController extends Controller
{
    public function settings()
    {
        return response()->json([
            'status' => 'success',
            'data' => ScooterSetting::current(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'unlock_fee' => 'required|numeric|min:0',
            'price_per_minute' => 'required|numeric|min:0',
            'minimum_amount' => 'required|numeric|min:0',
        ]);

        $settings = ScooterSetting::current();
        $settings->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Preço das trotinetes actualizado.',
            'data' => $settings->fresh(),
        ]);
    }

    public function stations(Request $request)
    {
        $stations = ScooterStation::query()
            ->withCount([
                'scooters',
                'availableScooters as available_count',
            ])
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $stations,
        ]);
    }

    public function storeStation(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'address' => 'nullable|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'is_active' => 'nullable|boolean',
        ]);

        $station = ScooterStation::create([
            ...$data,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Estação criada.',
            'data' => $station,
        ], 201);
    }

    public function updateStation(Request $request, string $id)
    {
        $station = ScooterStation::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'address' => 'nullable|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'is_active' => 'nullable|boolean',
        ]);
        $station->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Estação actualizada.',
            'data' => $station->fresh(),
        ]);
    }

    public function destroyStation(string $id)
    {
        $station = ScooterStation::withCount('scooters')->findOrFail($id);
        if ($station->scooters_count > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Move ou apaga as trotinetes desta estação antes.',
            ], 422);
        }
        $station->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Estação apagada.',
        ]);
    }

    public function scooters(Request $request)
    {
        $scooters = Scooter::query()
            ->with('station')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('station_id'), fn ($q) => $q->where('scooter_station_id', $request->station_id))
            ->orderBy('code')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $scooters,
        ]);
    }

    public function storeScooter(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:40|unique:scooters,code',
            'scooter_station_id' => 'required|exists:scooter_stations,id',
            'battery_percent' => 'nullable|integer|min:0|max:100',
            'status' => 'nullable|in:available,rented,maintenance',
        ]);

        $scooter = Scooter::create([
            'code' => strtoupper(trim($data['code'])),
            'scooter_station_id' => $data['scooter_station_id'],
            'battery_percent' => $data['battery_percent'] ?? 100,
            'status' => $data['status'] ?? 'available',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Trotinete adicionada.',
            'data' => $scooter->load('station'),
        ], 201);
    }

    public function updateScooter(Request $request, string $id)
    {
        $scooter = Scooter::findOrFail($id);
        $data = $request->validate([
            'code' => 'required|string|max:40|unique:scooters,code,'.$scooter->id,
            'scooter_station_id' => 'required|exists:scooter_stations,id',
            'battery_percent' => 'nullable|integer|min:0|max:100',
            'status' => 'required|in:available,rented,maintenance',
        ]);

        if ($scooter->status === 'rented' && $data['status'] !== 'rented') {
            $active = ScooterRental::query()
                ->where('scooter_id', $scooter->id)
                ->where('status', 'active')
                ->exists();
            if ($active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Há um aluguer activo nesta trotinete.',
                ], 422);
            }
        }

        $scooter->update([
            'code' => strtoupper(trim($data['code'])),
            'scooter_station_id' => $data['scooter_station_id'],
            'battery_percent' => $data['battery_percent'] ?? $scooter->battery_percent,
            'status' => $data['status'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Trotinete actualizada.',
            'data' => $scooter->fresh('station'),
        ]);
    }

    public function destroyScooter(string $id)
    {
        $scooter = Scooter::findOrFail($id);
        if ($scooter->status === 'rented') {
            return response()->json([
                'status' => 'error',
                'message' => 'Não podes apagar uma trotinete alugada.',
            ], 422);
        }
        $scooter->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Trotinete apagada.',
        ]);
    }

    public function rentals(Request $request)
    {
        $rentals = ScooterRental::query()
            ->with(['user', 'scooter', 'startStation', 'endStation'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $rentals,
        ]);
    }

    public function confirmPayment(string $id)
    {
        $rental = ScooterRental::findOrFail($id);
        if ($rental->status !== 'completed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Só podes confirmar pagamento de alugueres terminados.',
            ], 422);
        }
        $rental->update(['payment_status' => 'confirmed']);

        return response()->json([
            'status' => 'success',
            'message' => 'Pagamento confirmado.',
            'data' => $rental->fresh(['user', 'scooter', 'startStation', 'endStation']),
        ]);
    }
}
