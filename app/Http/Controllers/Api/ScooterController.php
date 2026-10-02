<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Scooter;
use App\Models\ScooterRental;
use App\Models\ScooterSetting;
use App\Models\ScooterStation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScooterController extends Controller
{
    public function pricing()
    {
        $settings = ScooterSetting::current();

        return response()->json([
            'status' => 'success',
            'data' => [
                'unlock_fee' => (float) $settings->unlock_fee,
                'price_per_minute' => (float) $settings->price_per_minute,
                'minimum_amount' => (float) $settings->minimum_amount,
            ],
        ]);
    }

    public function stations(Request $request)
    {
        $lat = $request->filled('latitude') ? (float) $request->latitude : null;
        $lng = $request->filled('longitude') ? (float) $request->longitude : null;

        $stations = ScooterStation::query()
            ->withCount(['availableScooters as available_count'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (ScooterStation $station) use ($lat, $lng) {
                $item = [
                    'id' => $station->id,
                    'name' => $station->name,
                    'address' => $station->address,
                    'latitude' => $station->latitude,
                    'longitude' => $station->longitude,
                    'available_count' => (int) $station->available_count,
                ];
                if ($lat !== null && $lng !== null) {
                    $item['distance_km'] = round($this->distanceKm($lat, $lng, $station->latitude, $station->longitude), 2);
                }

                return $item;
            });

        if ($lat !== null && $lng !== null) {
            $stations = $stations->sortBy('distance_km')->values();
        }

        return response()->json([
            'status' => 'success',
            'data' => $stations,
        ]);
    }

    public function showStation(string $id)
    {
        $station = ScooterStation::query()
            ->with(['availableScooters' => fn ($q) => $q->orderBy('code')])
            ->where('is_active', true)
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $station->id,
                'name' => $station->name,
                'address' => $station->address,
                'latitude' => $station->latitude,
                'longitude' => $station->longitude,
                'scooters' => $station->availableScooters->map(fn (Scooter $scooter) => [
                    'id' => $scooter->id,
                    'code' => $scooter->code,
                    'battery_percent' => $scooter->battery_percent,
                    'status' => $scooter->status,
                ]),
            ],
        ]);
    }

    public function active(Request $request)
    {
        $rental = ScooterRental::query()
            ->with(['scooter', 'startStation'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => $rental ? $this->rentalPayload($rental) : null,
        ]);
    }

    public function start(Request $request)
    {
        $data = $request->validate([
            'scooter_id' => 'nullable|exists:scooters,id',
            'code' => 'nullable|string|max:40',
            'payment_method' => 'required|in:cash,mpesa,emola,card',
            'payer_phone' => 'nullable|string|max:30',
        ]);

        if (empty($data['scooter_id']) && empty($data['code'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Indica a trotinete pelo código ou pela lista.',
            ], 422);
        }

        if (in_array($data['payment_method'], ['mpesa', 'emola'], true)
            && blank($data['payer_phone'] ?? null)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Indica o número do M-Pesa ou e-Mola.',
            ], 422);
        }

        $existing = ScooterRental::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->exists();
        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Já tens um aluguer activo. Termina-o antes de começar outro.',
            ], 422);
        }

        try {
            $rental = DB::transaction(function () use ($request, $data) {
                $scooterQuery = Scooter::query()->lockForUpdate();
                $scooter = ! empty($data['scooter_id'])
                    ? $scooterQuery->findOrFail($data['scooter_id'])
                    : $scooterQuery->whereRaw('upper(code) = ?', [strtoupper(trim($data['code']))])->firstOrFail();

                if ($scooter->status !== 'available') {
                    throw new \InvalidArgumentException('Esta trotinete não está disponível.');
                }

                $station = ScooterStation::query()->findOrFail($scooter->scooter_station_id);
                if (! $station->is_active) {
                    throw new \InvalidArgumentException('Esta estação está indisponível.');
                }

                $settings = ScooterSetting::current();
                $scooter->update(['status' => 'rented']);

                return ScooterRental::create([
                    'code' => 'TRT-'.now()->format('ymdHis').'-'.random_int(100, 999),
                    'user_id' => $request->user()->id,
                    'scooter_id' => $scooter->id,
                    'start_station_id' => $station->id,
                    'started_at' => now(),
                    'unlock_fee' => $settings->unlock_fee,
                    'price_per_minute' => $settings->price_per_minute,
                    'minimum_amount' => $settings->minimum_amount,
                    'payment_method' => $data['payment_method'],
                    'payer_phone' => $data['payer_phone'] ?? null,
                    'payment_status' => 'pending',
                    'status' => 'active',
                ]);
            });
        } catch (\InvalidArgumentException $error) {
            return response()->json([
                'status' => 'error',
                'message' => $error->getMessage(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Trotinete não encontrada.',
            ], 404);
        }

        $rental->load(['scooter', 'startStation']);

        return response()->json([
            'status' => 'success',
            'message' => 'Trotinete desbloqueada. O pagamento fica registado para confirmação no portal.',
            'data' => $this->rentalPayload($rental),
        ], 201);
    }

    public function end(Request $request, string $id)
    {
        $data = $request->validate([
            'station_id' => 'required|exists:scooter_stations,id',
            'payment_method' => 'nullable|in:cash,mpesa,emola,card',
            'payer_phone' => 'nullable|string|max:30',
        ]);

        $station = ScooterStation::query()
            ->where('is_active', true)
            ->findOrFail($data['station_id']);

        $rental = ScooterRental::query()
            ->with(['scooter', 'startStation', 'endStation'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->findOrFail($id);

        $quote = $rental->liveAmount();

        DB::transaction(function () use ($rental, $station, $quote, $data) {
            $scooter = Scooter::query()->lockForUpdate()->findOrFail($rental->scooter_id);
            $scooter->update([
                'status' => 'available',
                'scooter_station_id' => $station->id,
            ]);

            $rental->update([
                'end_station_id' => $station->id,
                'ended_at' => now(),
                'duration_minutes' => $quote['duration_minutes'],
                'amount' => $quote['amount'],
                'payment_method' => $data['payment_method'] ?? $rental->payment_method,
                'payer_phone' => $data['payer_phone'] ?? $rental->payer_phone,
                'status' => 'completed',
            ]);
        });

        $rental->refresh()->load(['scooter', 'startStation', 'endStation']);

        return response()->json([
            'status' => 'success',
            'message' => 'Aluguer terminado. O valor fica registado para confirmação no portal.',
            'data' => $this->rentalPayload($rental),
        ]);
    }

    private function rentalPayload(ScooterRental $rental): array
    {
        $live = $rental->status === 'active' ? $rental->liveAmount() : [
            'duration_minutes' => (int) ($rental->duration_minutes ?? 0),
            'amount' => (float) ($rental->amount ?? 0),
            'unlock_fee' => (float) $rental->unlock_fee,
            'price_per_minute' => (float) $rental->price_per_minute,
            'minimum_amount' => (float) $rental->minimum_amount,
        ];

        return [
            'id' => $rental->id,
            'code' => $rental->code,
            'status' => $rental->status,
            'payment_method' => $rental->payment_method,
            'payment_status' => $rental->payment_status,
            'payer_phone' => $rental->payer_phone,
            'started_at' => optional($rental->started_at)?->toIso8601String(),
            'ended_at' => optional($rental->ended_at)?->toIso8601String(),
            'duration_minutes' => $live['duration_minutes'],
            'amount' => $live['amount'],
            'unlock_fee' => $live['unlock_fee'],
            'price_per_minute' => $live['price_per_minute'],
            'minimum_amount' => $live['minimum_amount'],
            'scooter' => $rental->scooter ? [
                'id' => $rental->scooter->id,
                'code' => $rental->scooter->code,
                'battery_percent' => $rental->scooter->battery_percent,
            ] : null,
            'start_station' => $rental->startStation ? [
                'id' => $rental->startStation->id,
                'name' => $rental->startStation->name,
            ] : null,
            'end_station' => $rental->endStation ? [
                'id' => $rental->endStation->id,
                'name' => $rental->endStation->name,
            ] : null,
        ];
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
