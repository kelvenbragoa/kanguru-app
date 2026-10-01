<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderStatus;
use App\Models\OrderType;
use App\Models\ProductCategory;
use App\Models\ProductStatus;
use App\Models\Role;
use App\Models\Shop;
use App\Models\User;
use App\Models\VehicleStatus;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CatalogController extends Controller
{
    public function options()
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'shops' => Shop::query()->orderBy('name')->get(['id', 'name']),
                'product_categories' => ProductCategory::query()->orderBy('name')->get(['id', 'name']),
                'product_statuses' => ProductStatus::query()->orderBy('id')->get(['id', 'name']),
                'vehicle_types' => VehicleType::query()->orderBy('id')->get(['id', 'name']),
                'vehicle_statuses' => VehicleStatus::query()->orderBy('id')->get(['id', 'name', 'display_name']),
                'order_types' => OrderType::query()->orderBy('id')->get(['id', 'name']),
                'order_statuses' => OrderStatus::query()->orderBy('order_sequence')->get(['id', 'name', 'display_name', 'is_final']),
                'roles' => Role::query()->orderBy('id')->get(['id', 'name', 'display_name']),
                'customers' => User::query()
                    ->whereHas('role', fn ($q) => $q->where('name', 'customer'))
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'email']),
                'drivers' => User::query()
                    ->whereHas('role', fn ($q) => $q->where('name', 'driver'))
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name']),
            ],
        ]);
    }

    public function categories()
    {
        $categories = ProductCategory::query()
            ->withCount('products')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ]);
    }

    public function storeCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:product_categories,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $category = ProductCategory::create(['name' => $request->name]);

        return response()->json([
            'status' => 'success',
            'message' => 'Category created successfully',
            'data' => $category,
        ], 201);
    }

    public function updateCategory(Request $request, string $id)
    {
        $category = ProductCategory::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:product_categories,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $category->update(['name' => $request->name]);

        return response()->json([
            'status' => 'success',
            'message' => 'Category updated successfully',
            'data' => $category,
        ]);
    }

    public function destroyCategory(string $id)
    {
        $category = ProductCategory::findOrFail($id);

        if ($category->products()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete category with existing products',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Category deleted successfully',
        ]);
    }
}
