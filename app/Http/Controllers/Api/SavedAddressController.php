<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavedAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SavedAddressController extends Controller
{
    public const MAX_ADDRESSES = 15;

    public function index(Request $request)
    {
        $addresses = SavedAddress::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get()
            ->map(fn (SavedAddress $address) => $address->toApi())
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $addresses,
        ]);
    }

    public function store(Request $request)
    {
        $validator = $this->validator($request);
        if ($validator->fails()) {
            return $this->invalid($validator);
        }

        $userId = $request->user()->id;
        $count = SavedAddress::query()->where('user_id', $userId)->count();
        if ($count >= self::MAX_ADDRESSES) {
            return response()->json([
                'status' => 'error',
                'message' => 'Address limit reached',
            ], 422);
        }

        $isDefault = $count === 0 || $request->boolean('is_default');
        if ($isDefault) {
            SavedAddress::query()->where('user_id', $userId)->update(['is_default' => false]);
        }

        $address = SavedAddress::create([
            'user_id' => $userId,
            'label' => $request->label,
            'address' => $request->address,
            'city' => $request->input('city', 'Maputo'),
            'neighborhood' => $request->neighborhood,
            'reference' => $request->reference,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'is_default' => $isDefault,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $address->toApi(),
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $address = $this->owned($request, $id);
        $validator = $this->validator($request, true);
        if ($validator->fails()) {
            return $this->invalid($validator);
        }

        $payload = $request->only([
            'label',
            'address',
            'city',
            'neighborhood',
            'reference',
            'latitude',
            'longitude',
        ]);
        $address->fill($payload);
        $address->save();

        if ($request->boolean('is_default')) {
            $address->makeDefault();
        }

        return response()->json([
            'status' => 'success',
            'data' => $address->fresh()->toApi(),
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $address = $this->owned($request, $id);
        $userId = $address->user_id;
        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $next = SavedAddress::query()
                ->where('user_id', $userId)
                ->orderBy('id')
                ->first();
            $next?->update(['is_default' => true]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Address deleted',
        ]);
    }

    public function setDefault(Request $request, string $id)
    {
        $address = $this->owned($request, $id);
        $address->makeDefault();

        return response()->json([
            'status' => 'success',
            'data' => $address->fresh()->toApi(),
        ]);
    }

    private function owned(Request $request, string $id): SavedAddress
    {
        return SavedAddress::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
    }

    private function validator(Request $request, bool $partial = false)
    {
        $required = $partial ? 'sometimes|required' : 'required';

        return Validator::make($request->all(), [
            'label' => $required.'|string|max:40',
            'address' => $required.'|string|max:500',
            'city' => 'nullable|string|max:80',
            'neighborhood' => 'nullable|string|max:80',
            'reference' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_default' => 'nullable|boolean',
        ]);
    }

    private function invalid($validator)
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422);
    }
}
