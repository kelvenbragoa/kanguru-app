<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rotas públicas (sem autenticação)
Route::prefix('v1')->group(function () {
    
    // Autenticação
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    // Rastreamento público (apenas com código do pedido)
    Route::get('tracking/{orderCode}', [TrackingController::class, 'getTracking']);
    
    // Produtos públicos (para catálogo)
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::get('products/category/{categoryId}', [ProductController::class, 'getByCategory']);
    Route::get('products/shop/{shopId}', [ProductController::class, 'getByShop']);
    
    // Lojas públicas
    Route::get('shops', [ShopController::class, 'index']);
    Route::get('shops/{id}', [ShopController::class, 'show']);
});

// Rotas protegidas (com autenticação)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // Autenticação
    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::put('change-password', [AuthController::class, 'changePassword']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
    });

    // Pedidos
    Route::apiResource('orders', OrderController::class);
    Route::post('orders/{id}/assign-driver', [OrderController::class, 'assignDriver']);
    
    // Rastreamento
    Route::prefix('tracking')->group(function () {
        Route::put('orders/{orderId}/status', [TrackingController::class, 'updateStatus']);
        Route::get('orders/{orderId}/location', [TrackingController::class, 'getDriverLocation']);
        Route::get('driver/orders', [TrackingController::class, 'getDriverOrders']);
    });

    // Veículos
    Route::apiResource('vehicles', VehicleController::class);
    Route::get('vehicles-available', [VehicleController::class, 'getAvailable']);
    Route::post('vehicles/{id}/assign-driver', [VehicleController::class, 'assignDriver']);

    // Produtos (operações administrativas)
    Route::apiResource('products', ProductController::class)->except(['index', 'show']);
    
    // Lojas (operações administrativas)
    Route::apiResource('shops', ShopController::class)->except(['index', 'show']);
    
    // Pagamentos
    Route::apiResource('payments', PaymentController::class);
    Route::post('payments/{id}/confirm', [PaymentController::class, 'confirm']);
    Route::post('payments/{id}/refund', [PaymentController::class, 'refund']);
    Route::get('payments/order/{orderId}', [PaymentController::class, 'getByOrder']);
});

// Rotas específicas por role
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    
    // Rotas para Administradores e Gerentes
    Route::middleware('role:admin,manager')->group(function () {
        // Dashboard e relatórios
        Route::get('dashboard/stats', function () {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_orders' => \App\Models\Order::count(),
                    'pending_orders' => \App\Models\Order::pending()->count(),
                    'active_vehicles' => \App\Models\Vehicle::available()->count(),
                    'total_revenue' => \App\Models\Payment::completed()->sum('amount'),
                ]
            ]);
        });

        Route::apiResource('users', UserController::class);
        Route::patch('users/{id}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::get('users/role/{roleName}', [UserController::class, 'getUsersByRole']);
        Route::get('drivers/available', [UserController::class, 'getAvailableDrivers']);
        
        // Gestão completa de usuários
        // Route::get('users', function (Request $request) {
        //     $users = \App\Models\User::with(['role', 'profile'])->paginate(15);
        //     return response()->json(['status' => 'success', 'data' => $users]);
        // });
    });
    
    // Rotas para Motoristas
    Route::middleware('role:driver')->group(function () {
        Route::get('driver/dashboard', function () {
            $user = auth()->user();
            $activeOrders = \App\Models\Order::where('agent_user_id', $user->id)
                ->whereHas('orderStatus', fn($q) => $q->where('is_final', false))
                ->count();
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'active_orders' => $activeOrders,
                    'vehicle' => $user->vehicles()->first(),
                ]
            ]);
        });
    });
    
    // Rotas para Clientes
    Route::middleware('role:customer')->group(function () {
        Route::get('customer/orders', function () {
            $orders = \App\Models\Order::where('user_id', auth()->id())
                ->with(['orderStatus', 'orderType', 'agent', 'vehicle'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);
            
            return response()->json(['status' => 'success', 'data' => $orders]);
        });
    });
});
