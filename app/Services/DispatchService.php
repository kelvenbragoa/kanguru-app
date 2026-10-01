<?php

namespace App\Services;

use App\Models\Order;
use App\Models\TrackingOrder;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DispatchService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    public function dispatchCreatedOrder(Order $order): ?User
    {
        $order->loadMissing(['user', 'originLocation', 'orderStatus']);

        if ($order->user) {
            $this->notifications->notify(
                $order->user,
                'Pedido criado',
                'O pedido '.$order->code.' foi registado.',
                'order_created',
                ['order_id' => $order->id, 'order_code' => $order->code]
            );
        }

        return $this->tryAssign($order, true, true);
    }

    public function tryAssign(Order $order, bool $notifyDrivers = true, bool $notifyStaff = false): ?User
    {
        return DB::transaction(function () use ($order, $notifyDrivers, $notifyStaff) {
            $locked = Order::query()
                ->where('id', $order->id)
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return null;
            }

            if ($locked->agent_user_id) {
                return $locked->agent;
            }

            $locked->loadMissing(['user', 'originLocation', 'orderStatus', 'agent']);
            $driver = $this->findBestDriver($locked);
            if ($driver) {
                $this->assignOrderToDriver($locked, $driver, 'Motorista atribuído automaticamente');

                return $driver;
            }

            if ($notifyDrivers) {
                $this->notifyOnlineDriversOfOffer($locked);
            }
            if ($notifyStaff) {
                $this->notifyStaffOfUnassigned($locked);
            }

            return null;
        });
    }

    public function unassignOrder(Order $order, ?User $actor = null): void
    {
        $order->loadMissing('orderStatus');

        if ($order->orderStatus?->is_final) {
            throw new \RuntimeException('Cannot unassign a finished order');
        }

        $this->releaseVehicle($order);

        $order->update([
            'agent_user_id' => null,
            'vehicle_id' => null,
            'order_status_id' => 2,
        ]);

        TrackingOrder::create([
            'order_id' => $order->id,
            'order_status_id' => 2,
            'description' => 'Motorista removido pela operação',
            'local' => $order->origin,
            'updated_by' => $actor?->id,
        ]);
    }

    public function board(): array
    {
        $unassigned = Order::query()
            ->whereNull('agent_user_id')
            ->whereHas('orderStatus', fn ($q) => $q->whereIn('name', ['pending', 'confirmed']))
            ->with(['user', 'shop', 'orderType', 'orderStatus', 'payments'])
            ->orderBy('created_at')
            ->limit(50)
            ->get();

        $active = Order::query()
            ->whereNotNull('agent_user_id')
            ->whereHas('orderStatus', fn ($q) => $q->where('is_final', false))
            ->with(['user', 'agent.profile', 'vehicle', 'shop', 'orderType', 'orderStatus'])
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get()
            ->map(function (Order $order) {
                $payload = $order->toArray();
                $payload['current_location'] = $order->liveLocation();

                return $payload;
            });

        $drivers = User::query()
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->where('name', 'driver'))
            ->with(['profile', 'vehicles'])
            ->orderByDesc('is_online')
            ->orderBy('name')
            ->get()
            ->map(function (User $driver) {
                return [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'phone' => $driver->profile?->phone,
                    'is_online' => (bool) $driver->is_online,
                    'is_busy' => $this->driverIsBusy($driver),
                    'last_seen_at' => $driver->last_seen_at,
                    'last_latitude' => $driver->last_latitude !== null ? (float) $driver->last_latitude : null,
                    'last_longitude' => $driver->last_longitude !== null ? (float) $driver->last_longitude : null,
                    'location_updated_at' => $driver->location_updated_at,
                    'vehicle' => $driver->vehicles->first(),
                ];
            })
            ->values();

        return [
            'unassigned' => $unassigned,
            'active' => $active,
            'drivers' => $drivers,
            'online_count' => $drivers->where('is_online', true)->count(),
            'busy_count' => $drivers->where('is_busy', true)->count(),
            'unassigned_count' => $unassigned->count(),
        ];
    }

    public function claimPendingForDriver(User $driver): int
    {
        if ($this->driverIsBusy($driver) || $driver->vehicles()->doesntExist()) {
            return 0;
        }

        return (int) DB::transaction(function () use ($driver) {
            if ($this->driverIsBusy($driver)) {
                return 0;
            }

            $order = Order::query()
                ->whereNull('agent_user_id')
                ->whereHas('orderStatus', fn ($q) => $q->whereIn('name', ['pending', 'confirmed']))
                ->orderBy('created_at')
                ->lockForUpdate()
                ->first();

            if (! $order) {
                return 0;
            }

            $this->assignOrderToDriver($order, $driver, 'Pedido atribuído ao ficar online');

            return 1;
        });
    }

    public function assignOrderToDriver(Order $order, User $driver, string $trackingDescription): void
    {
        $order->loadMissing('user');
        $vehicle = $driver->vehicles()->first();

        $order->update([
            'agent_user_id' => $driver->id,
            'vehicle_id' => $vehicle?->id,
            'order_status_id' => 3,
        ]);

        if ($vehicle) {
            $vehicle->update(['vehicle_status_id' => 2]);
        }

        TrackingOrder::create([
            'order_id' => $order->id,
            'order_status_id' => 3,
            'description' => $trackingDescription,
            'local' => $order->origin,
            'updated_by' => $driver->id,
        ]);

        $this->notifications->notify(
            $driver,
            'Novo pedido',
            'Foi-lhe atribuído o pedido '.$order->code.'.',
            'order_assigned',
            ['order_id' => $order->id, 'order_code' => $order->code]
        );

        if ($order->user) {
            $this->notifications->notify(
                $order->user,
                'Motorista a caminho',
                $driver->name.' ficou responsável pelo pedido '.$order->code.'.',
                'driver_assigned',
                ['order_id' => $order->id, 'order_code' => $order->code]
            );
        }
    }

    public function notifyOrderCancelled(Order $order): void
    {
        if ($order->agent) {
            $this->notifications->notify(
                $order->agent,
                'Pedido cancelado',
                'O cliente cancelou o pedido '.$order->code.'.',
                'order_cancelled',
                ['order_id' => $order->id, 'order_code' => $order->code]
            );
        }
    }

    public function notifyStatusChange(Order $order, string $statusLabel): void
    {
        if (! $order->user) {
            return;
        }

        $this->notifications->notify(
            $order->user,
            'Pedido atualizado',
            'O pedido '.$order->code.' está agora: '.$statusLabel.'.',
            'order_status',
            [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'status' => $order->orderStatus?->name,
            ]
        );
    }

    public function releaseVehicle(Order $order): void
    {
        if ($order->vehicle_id) {
            Vehicle::where('id', $order->vehicle_id)->update(['vehicle_status_id' => 1]);
        }
    }

    private function findBestDriver(Order $order): ?User
    {
        $candidates = $this->onlineIdleDrivers();
        if ($candidates->isEmpty()) {
            return null;
        }

        $origin = $order->originLocation;
        $originLat = $origin?->latitude !== null ? (float) $origin->latitude : null;
        $originLng = $origin?->longitude !== null ? (float) $origin->longitude : null;

        if ($originLat !== null && $originLng !== null) {
            return $candidates
                ->sortBy(function (User $driver) use ($originLat, $originLng) {
                    $distance = $this->distanceKm(
                        $originLat,
                        $originLng,
                        $driver->last_latitude !== null ? (float) $driver->last_latitude : null,
                        $driver->last_longitude !== null ? (float) $driver->last_longitude : null,
                    );

                    return $distance ?? 99999;
                })
                ->first();
        }

        return $candidates->sortByDesc('last_seen_at')->first();
    }

    private function onlineIdleDrivers(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where('is_online', true)
            ->where(function ($query) {
                $query->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '>=', now()->subMinutes(5));
            })
            ->whereHas('role', fn ($q) => $q->where('name', 'driver'))
            ->whereHas('vehicles')
            ->with('vehicles')
            ->get()
            ->reject(fn (User $driver) => $this->driverIsBusy($driver))
            ->values();
    }

    private function driverIsBusy(User $driver): bool
    {
        return Order::where('agent_user_id', $driver->id)
            ->whereHas('orderStatus', fn ($q) => $q->where('is_final', false))
            ->exists();
    }

    private function notifyStaffOfUnassigned(Order $order): void
    {
        $staff = User::query()
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->whereIn('name', ['admin', 'manager']))
            ->get();

        foreach ($staff as $user) {
            $this->notifications->notify(
                $user,
                'Pedido sem motorista',
                'O pedido '.$order->code.' está à espera de motorista.',
                'order_unassigned',
                ['order_id' => $order->id, 'order_code' => $order->code]
            );
        }
    }

    private function notifyOnlineDriversOfOffer(Order $order): void
    {
        $drivers = User::query()
            ->where('is_active', true)
            ->where('is_online', true)
            ->whereHas('role', fn ($q) => $q->where('name', 'driver'))
            ->get();

        foreach ($drivers as $driver) {
            $this->notifications->notify(
                $driver,
                'Pedido disponível',
                'Há um novo pedido perto de si: '.$order->code.'.',
                'order_available',
                ['order_id' => $order->id, 'order_code' => $order->code]
            );
        }
    }

    private function distanceKm(?float $lat1, ?float $lng1, ?float $lat2, ?float $lng2): ?float
    {
        if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) {
            return null;
        }

        $earth = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
