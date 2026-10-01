<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderType;
use App\Services\TaxiFare;
use Illuminate\Http\Request;

class TaxiController extends Controller
{
    public function quote(Request $request)
    {
        $data = $request->validate([
            'origin_latitude' => 'required|numeric|between:-90,90',
            'origin_longitude' => 'required|numeric|between:-180,180',
            'destination_latitude' => 'required|numeric|between:-90,90',
            'destination_longitude' => 'required|numeric|between:-180,180',
        ]);

        $type = OrderType::query()->firstOrCreate(['name' => 'Táxi']);

        return response()->json([
            'status' => 'success',
            'data' => array_merge(TaxiFare::quote(
                (float) $data['origin_latitude'],
                (float) $data['origin_longitude'],
                (float) $data['destination_latitude'],
                (float) $data['destination_longitude'],
            ), [
                'order_type_id' => $type->id,
            ]),
        ]);
    }
}
