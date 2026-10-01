<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShopController extends Controller
{
    /**
     * Display a listing of shops
     */
    public function index(Request $request)
    {
        $query = Shop::with(['products' => function($q) {
            $q->active()->with('productCategory')->limit(5);
        }]);

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('address', 'like', '%' . $request->search . '%');
            });
        }

        $perPage = min(max($request->integer('per_page', 15), 1), 50);
        $shops = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $shops
        ]);
    }

    /**
     * Store a newly created shop
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255|unique:shops',
            'description' => 'nullable|string|max:1000',
            'delivery_fee' => 'nullable|numeric|min:0',
            'image_url' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $shop = Shop::create($request->only([
            'name', 'address', 'phone', 'email', 'description', 'delivery_fee', 'image_url',
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Shop created successfully',
            'data' => $shop
        ], 201);
    }

    /**
     * Display the specified shop
     */
    public function show(string $id)
    {
        $shop = Shop::with(['products' => function($q) {
            $q->active()->with(['productCategory', 'productStatus']);
        }])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $shop
        ]);
    }

    /**
     * Update the specified shop
     */
    public function update(Request $request, string $id)
    {
        $shop = Shop::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'address' => 'sometimes|required|string|max:500',
            'phone' => 'sometimes|required|string|max:20',
            'email' => 'sometimes|required|email|max:255|unique:shops,email,' . $id,
            'description' => 'nullable|string|max:1000',
            'delivery_fee' => 'nullable|numeric|min:0',
            'image_url' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $shop->update($request->only([
            'name', 'address', 'phone', 'email', 'description', 'delivery_fee', 'image_url',
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Shop updated successfully',
            'data' => $shop
        ]);
    }

    /**
     * Remove the specified shop
     */
    public function destroy(string $id)
    {
        $shop = Shop::findOrFail($id);

        // Verificar se a loja tem produtos
        if ($shop->products()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete shop with existing products'
            ], 422);
        }

        $shop->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Shop deleted successfully'
        ]);
    }
}
