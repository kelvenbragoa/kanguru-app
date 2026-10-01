<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DispatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DriverAvailabilityController extends Controller
{
    public function __construct(private DispatchService $dispatch)
    {
    }

    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'data' => [
                'is_online' => (bool) $user->is_online,
                'last_seen_at' => $user->last_seen_at,
                'last_latitude' => $user->last_latitude !== null ? (float) $user->last_latitude : null,
                'last_longitude' => $user->last_longitude !== null ? (float) $user->last_longitude : null,
                'location_updated_at' => $user->location_updated_at,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'is_online' => 'required|boolean',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $wasOnline = (bool) $user->is_online;
        $goingOnline = $request->boolean('is_online');

        $payload = [
            'is_online' => $goingOnline,
            'last_seen_at' => now(),
        ];

        if ($request->filled('latitude') && $request->filled('longitude')) {
            $payload['last_latitude'] = $request->latitude;
            $payload['last_longitude'] = $request->longitude;
            $payload['location_updated_at'] = now();
        }

        $user->update($payload);

        $assigned = 0;
        if ($goingOnline && ! $wasOnline) {
            $assigned = $this->dispatch->claimPendingForDriver($user->fresh());
        }

        return response()->json([
            'status' => 'success',
            'message' => $goingOnline ? 'Driver is online' : 'Driver is offline',
            'data' => [
                'is_online' => $goingOnline,
                'assigned_pending' => $assigned,
                'last_seen_at' => $user->last_seen_at,
                'last_latitude' => $user->last_latitude !== null ? (float) $user->last_latitude : null,
                'last_longitude' => $user->last_longitude !== null ? (float) $user->last_longitude : null,
            ],
        ]);
    }
}
