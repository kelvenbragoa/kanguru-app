<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SupportMessage;
use App\Models\TrackingOrder;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Services\DispatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DriverAccountController extends Controller
{
    public function __construct(private DispatchService $dispatch)
    {
    }

    public function vehicle(Request $request)
    {
        $vehicle = $request->user()->vehicles()->with('vehicleType')->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'vehicle' => $vehicle,
                'types' => VehicleType::query()->orderBy('id')->get(['id', 'name']),
            ],
        ]);
    }

    public function saveVehicle(Request $request)
    {
        $data = Validator::make($request->all(), [
            'license_plate_number' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'color' => 'required|string|max:255',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
        ])->validate();

        $user = $request->user();
        $vehicle = $user->vehicles()->first();

        $plateTaken = Vehicle::query()
            ->where('license_plate_number', $data['license_plate_number'])
            ->when($vehicle, fn ($query) => $query->where('id', '!=', $vehicle->id))
            ->exists();

        if ($plateTaken) {
            return response()->json([
                'status' => 'error',
                'message' => 'Esta matrícula já está registada.',
            ], 422);
        }

        if ($vehicle) {
            $vehicle->update($data);
        } else {
            $vehicle = Vehicle::create([
                ...$data,
                'driver_id' => $user->id,
                'vehicle_status_id' => 1,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Veículo guardado',
            'data' => $vehicle->fresh()->load('vehicleType'),
        ]);
    }

    public function settlement(Request $request)
    {
        $payments = $this->cashInHand($request->user()->id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'amount' => round((float) $payments->sum('amount'), 2),
                'count' => $payments->count(),
                'orders' => $payments->map(fn (Payment $payment) => [
                    'order_id' => $payment->order_id,
                    'code' => $payment->order?->code,
                    'amount' => (float) $payment->amount,
                ])->values(),
            ],
        ]);
    }

    public function requestSettlement(Request $request)
    {
        $note = trim((string) $request->input('note', ''));
        $payments = $this->cashInHand($request->user()->id);
        $amount = round((float) $payments->sum('amount'), 2);
        $codes = $payments->map(fn (Payment $payment) => $payment->order?->code)->filter()->implode(', ');

        $message = 'Pedido de fecho de conta. Dinheiro em mão: '.$amount.' MT.';
        if ($codes !== '') {
            $message .= ' Pedidos: '.$codes.'.';
        }
        if ($note !== '') {
            $message .= ' '.$note;
        }

        SupportMessage::create([
            'user_id' => $request->user()->id,
            'subject' => 'Fecho de conta',
            'message' => mb_substr($message, 0, 2000),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pedido enviado. A equipa confirma o fecho no portal.',
            'data' => ['amount' => $amount],
        ], 201);
    }

    public function release(Request $request, string $id)
    {
        $order = Order::with('orderStatus')->findOrFail($id);

        if ($order->agent_user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Este pedido não está consigo.',
            ], 403);
        }

        if (! in_array($order->orderStatus?->name, ['assigned', 'collecting'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Já não é possível devolver este pedido.',
            ], 422);
        }

        $this->dispatch->releaseVehicle($order);

        $order->update([
            'agent_user_id' => null,
            'vehicle_id' => null,
            'order_status_id' => 2,
        ]);

        TrackingOrder::create([
            'order_id' => $order->id,
            'order_status_id' => 2,
            'description' => 'Motorista devolveu o pedido',
            'local' => $order->origin,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pedido devolvido',
            'data' => $order->fresh()->load([
                'user',
                'shop',
                'vehicle',
                'orderType',
                'orderStatus',
                'orderItems.product',
                'payments',
            ]),
        ]);
    }

    private function cashInHand(int $driverId)
    {
        return Payment::query()
            ->where('payment_method', 'cash')
            ->where('status', 'completed')
            ->whereHas('order', function ($query) use ($driverId) {
                $query->where('agent_user_id', $driverId)
                    ->where('order_status_id', 8);
            })
            ->with('order:id,code')
            ->get();
    }
}
