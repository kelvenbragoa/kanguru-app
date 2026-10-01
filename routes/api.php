<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\TaxiController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\DriverAccountController;
use App\Http\Controllers\Api\DriverAvailabilityController;
use App\Http\Controllers\Api\DriverEarningsController;
use App\Http\Controllers\Api\SavedAddressController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DispatchController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\CustomerAccountController;

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
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
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
    Route::get('shops/{id}/reviews', [CustomerAccountController::class, 'shopReviews']);
    Route::get('shops/{id}', [ShopController::class, 'show']);
});

// Rotas protegidas (com autenticação)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // Autenticação
    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::get('user', [AuthController::class, 'user']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::put('change-password', [AuthController::class, 'changePassword']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
        Route::post('device-token', [NotificationController::class, 'storeDeviceToken']);
    });

    Route::get('addresses', [SavedAddressController::class, 'index']);
    Route::post('addresses', [SavedAddressController::class, 'store']);
    Route::put('addresses/{id}', [SavedAddressController::class, 'update']);
    Route::delete('addresses/{id}', [SavedAddressController::class, 'destroy']);
    Route::post('addresses/{id}/default', [SavedAddressController::class, 'setDefault']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead']);

    // Pedidos
    Route::apiResource('orders', OrderController::class);
    Route::post('orders/{id}/cancel', [OrderController::class, 'destroy']);
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

    Route::post('uploads', [UploadController::class, 'store']);
    Route::post('taxi/quote', [TaxiController::class, 'quote']);
    Route::get('me/summary', [CustomerAccountController::class, 'summary']);
    Route::put('me/preferences', [CustomerAccountController::class, 'updatePreferences']);
    Route::get('favorites', [CustomerAccountController::class, 'favorites']);
    Route::post('favorites', [CustomerAccountController::class, 'storeFavorite']);
    Route::delete('favorites/{id}', [CustomerAccountController::class, 'destroyFavorite']);
    Route::get('reviews', [CustomerAccountController::class, 'reviews']);
    Route::post('reviews', [CustomerAccountController::class, 'storeReview']);
    Route::post('coupons/validate', [CustomerAccountController::class, 'validateCoupon']);
    Route::get('payment-methods', [CustomerAccountController::class, 'paymentMethods']);
    Route::post('payment-methods', [CustomerAccountController::class, 'storePaymentMethod']);
    Route::delete('payment-methods/{id}', [CustomerAccountController::class, 'destroyPaymentMethod']);
    Route::post('payment-methods/{id}/default', [CustomerAccountController::class, 'defaultPaymentMethod']);
    Route::post('support', [CustomerAccountController::class, 'support']);
});

// Rotas específicas por role
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    
    // Rotas para Administradores e Gerentes
    Route::middleware('role:admin,manager')->group(function () {
        Route::get('dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('dashboard/reports', [DashboardController::class, 'reports']);

        Route::get('dispatch/board', [DispatchController::class, 'board']);
        Route::post('dispatch/orders/{id}/retry', [DispatchController::class, 'retry']);
        Route::post('dispatch/orders/{id}/unassign', [DispatchController::class, 'unassign']);

        Route::apiResource('users', UserController::class);
        Route::patch('users/{id}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::get('users/role/{roleName}', [UserController::class, 'getUsersByRole']);
        Route::get('drivers/available', [UserController::class, 'getAvailableDrivers']);

        Route::get('catalog/options', [CatalogController::class, 'options']);
        Route::get('product-categories', [CatalogController::class, 'categories']);
        Route::post('product-categories', [CatalogController::class, 'storeCategory']);
        Route::put('product-categories/{id}', [CatalogController::class, 'updateCategory']);
        Route::delete('product-categories/{id}', [CatalogController::class, 'destroyCategory']);
    });
    
    // Rotas para Motoristas
    Route::middleware('role:driver')->prefix('driver')->group(function () {
        Route::get('dashboard', function () {
            $user = request()->user();
            $activeOrders = \App\Models\Order::where('agent_user_id', $user->id)
                ->whereHas('orderStatus', fn ($q) => $q->where('is_final', false))
                ->count();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'active_orders' => $activeOrders,
                    'vehicle' => $user->vehicles()->first(),
                ]
            ]);
        });

        Route::get('available-orders', [OrderController::class, 'availableForDriver']);
        Route::get('orders', [OrderController::class, 'driverOrders']);
        Route::post('orders/{id}/accept', [OrderController::class, 'accept']);
        Route::post('orders/{id}/status', [OrderController::class, 'advanceStatus']);
        Route::post('orders/{id}/location', [TrackingController::class, 'updateLocation']);
        Route::get('availability', [DriverAvailabilityController::class, 'show']);
        Route::post('availability', [DriverAvailabilityController::class, 'update']);
        Route::get('earnings', [DriverEarningsController::class, 'show']);
        Route::get('vehicle', [DriverAccountController::class, 'vehicle']);
        Route::put('vehicle', [DriverAccountController::class, 'saveVehicle']);
        Route::get('settlement', [DriverAccountController::class, 'settlement']);
        Route::post('settlement', [DriverAccountController::class, 'requestSettlement']);
        Route::post('orders/{id}/release', [DriverAccountController::class, 'release']);
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
