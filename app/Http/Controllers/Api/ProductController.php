<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        $query = Product::with(['shop', 'productCategory', 'productStatus']);

        // Filtros opcionais
        if ($request->has('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        if ($request->has('category_id')) {
            $query->where('product_category_id', $request->category_id);
        }

        if ($request->has('status')) {
            $query->whereHas('productStatus', function ($q) use ($request) {
                $q->where('name', $request->status);
            });
        }

        if ($request->has('active')) {
            $query->active();
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $products = $query->orderBy('name')->paginate(
            min(max($request->integer('per_page', 15), 1), 50)
        );

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_id' => 'required|exists:shops,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'product_category_id' => 'required|exists:product_categories,id',
            'product_status_id' => 'required|exists:product_statuses,id',
            'weight' => 'nullable|numeric|min:0',
            'image' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Product created successfully',
            'data' => $product->load(['shop', 'productCategory', 'productStatus'])
        ], 201);
    }

    /**
     * Display the specified product
     */
    public function show(string $id)
    {
        $product = Product::with(['shop', 'productCategory', 'productStatus'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $product
        ]);
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'shop_id' => 'sometimes|required|exists:shops,id',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'product_category_id' => 'sometimes|required|exists:product_categories,id',
            'product_status_id' => 'sometimes|required|exists:product_statuses,id',
            'weight' => 'nullable|numeric|min:0',
            'image' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $product->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Product updated successfully',
            'data' => $product->load(['shop', 'productCategory', 'productStatus'])
        ]);
    }

    /**
     * Remove the specified product
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);

        // Verificar se o produto tem pedidos associados
        $orderItems = $product->orderItems()->count();

        if ($orderItems > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete product with existing orders'
            ], 422);
        }

        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * Get products by category
     */
    public function getByCategory(string $categoryId)
    {
        $products = Product::where('product_category_id', $categoryId)
            ->active()
            ->with(['shop', 'productCategory', 'productStatus'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * Get products by shop
     */
    public function getByShop(string $shopId)
    {
        $products = Product::where('shop_id', $shopId)
            ->active()
            ->with(['productCategory', 'productStatus'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }
}
