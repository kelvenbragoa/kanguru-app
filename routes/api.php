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
use App\Http\Controllers\Api\ScooterController;
use App\Http\Controllers\Api\ScooterAdminController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\HotelController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\OrderExceptionController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\DriverKycController;
use App\Http\Controllers\Api\DriverWalletController;
use App\Http\Controllers\Api\LostItemController;

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
    Route::get('shops/{id}/rooms', [HotelController::class, 'rooms']);
    Route::get('shops/{id}', [ShopController::class, 'show']);
    Route::get('events', [EventController::class, 'index']);
    Route::get('events/categories', [EventController::class, 'categories']);
    Route::get('events/promoters', [EventController::class, 'promoters']);
    Route::get('events/promoters/{slug}', [EventController::class, 'showPromoter']);
    Route::get('events/mticket-download', [EventController::class, 'downloadInfo']);
    Route::get('events/{id}', [EventController::class, 'show']);
    Route::get('services', [ServiceController::class, 'index']);
    Route::get('services/{id}', [ServiceController::class, 'show']);
    Route::post('leads', [\App\Http\Controllers\Api\SiteLeadController::class, 'store']);
    Route::get('cities', function () {
        return response()->json([
            'status' => 'success',
            'data' => config('kanguro.cities', []),
        ]);
    });
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
    Route::post('orders/{id}/shop-accept', [OrderController::class, 'shopAccept']);
    Route::post('orders/{id}/shop-ready', [OrderController::class, 'shopReady']);
    Route::post('orders/{id}/emergency', [OrderController::class, 'emergency']);
    Route::post('orders/{id}/rate', [OrderController::class, 'rate']);
    Route::get('orders/{id}/share', [OrderController::class, 'share']);
    
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
    Route::get('scooters/pricing', [ScooterController::class, 'pricing']);
    Route::get('scooters/stations', [ScooterController::class, 'stations']);
    Route::get('scooters/stations/{id}', [ScooterController::class, 'showStation']);
    Route::get('scooters/rentals/active', [ScooterController::class, 'active']);
    Route::get('scooters/rentals/history', [ScooterController::class, 'history']);
    Route::post('scooters/rentals/start', [ScooterController::class, 'start']);
    Route::post('scooters/rentals/{id}/end', [ScooterController::class, 'end']);
    Route::get('me/summary', [CustomerAccountController::class, 'summary']);
    Route::get('me/activity', [CustomerAccountController::class, 'activity']);
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

    Route::post('hotel/quote', [HotelController::class, 'quote']);
    Route::get('hotel/bookings', [HotelController::class, 'index']);
    Route::post('hotel/bookings', [HotelController::class, 'store']);
    Route::get('hotel/bookings/{id}', [HotelController::class, 'show']);
    Route::post('hotel/bookings/{id}/cancel', [HotelController::class, 'cancel']);

    Route::post('service-bookings', [ServiceController::class, 'book']);
    Route::get('service-bookings', [ServiceController::class, 'myBookings']);
    Route::get('service-bookings/{id}', [ServiceController::class, 'showBooking']);
    Route::post('service-bookings/{id}/cancel', [ServiceController::class, 'cancel']);

    Route::get('orders/{orderId}/exceptions', [OrderExceptionController::class, 'forOrder']);
    Route::post('order-exceptions', [OrderExceptionController::class, 'store']);
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

        Route::get('order-exceptions/types', [OrderExceptionController::class, 'types']);
        Route::get('order-exceptions', [OrderExceptionController::class, 'index']);
        Route::post('order-exceptions/{id}/resolve', [OrderExceptionController::class, 'resolve']);

        Route::get('finance/summary', [FinanceController::class, 'summary']);
        Route::get('finance/ledger', [FinanceController::class, 'ledger']);
        Route::get('finance/payouts', [FinanceController::class, 'payouts']);
        Route::post('finance/payouts', [FinanceController::class, 'createPayouts']);
        Route::post('finance/payouts/{id}/pay', [FinanceController::class, 'markPaid']);

        Route::get('driver-wallets', [DriverWalletController::class, 'adminIndex']);
        Route::get('driver-wallets/pending-topups', [DriverWalletController::class, 'adminPendingTopups']);
        Route::get('driver-wallets/{userId}', [DriverWalletController::class, 'adminShow']);
        Route::post('driver-wallets/{userId}/type', [DriverWalletController::class, 'adminSetType']);
        Route::post('driver-wallets/{userId}/credit', [DriverWalletController::class, 'adminCredit']);
        Route::post('driver-wallet-topups/{id}/confirm', [DriverWalletController::class, 'adminConfirmTopup']);
        Route::post('driver-wallet-topups/{id}/reject', [DriverWalletController::class, 'adminRejectTopup']);

        Route::get('driver-kyc', [DriverKycController::class, 'adminIndex']);
        Route::post('driver-kyc/{id}/review', [DriverKycController::class, 'review']);
        Route::get('lost-items', [LostItemController::class, 'index']);
        Route::post('lost-items/{id}/support', [LostItemController::class, 'supportUpdate']);

        Route::apiResource('users', UserController::class);
        Route::patch('users/{id}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::get('users/role/{roleName}', [UserController::class, 'getUsersByRole']);
        Route::get('drivers/available', [UserController::class, 'getAvailableDrivers']);

        Route::get('catalog/options', [CatalogController::class, 'options']);
        Route::get('product-categories', [CatalogController::class, 'categories']);
        Route::post('product-categories', [CatalogController::class, 'storeCategory']);
        Route::put('product-categories/{id}', [CatalogController::class, 'updateCategory']);
        Route::delete('product-categories/{id}', [CatalogController::class, 'destroyCategory']);

        Route::get('scooter-settings', [ScooterAdminController::class, 'settings']);
        Route::put('scooter-settings', [ScooterAdminController::class, 'updateSettings']);
        Route::get('scooter-stations', [ScooterAdminController::class, 'stations']);
        Route::post('scooter-stations', [ScooterAdminController::class, 'storeStation']);
        Route::put('scooter-stations/{id}', [ScooterAdminController::class, 'updateStation']);
        Route::delete('scooter-stations/{id}', [ScooterAdminController::class, 'destroyStation']);
        Route::get('scooters', [ScooterAdminController::class, 'scooters']);
        Route::post('scooters', [ScooterAdminController::class, 'storeScooter']);
        Route::put('scooters/{id}', [ScooterAdminController::class, 'updateScooter']);
        Route::delete('scooters/{id}', [ScooterAdminController::class, 'destroyScooter']);
        Route::get('scooter-rentals', [ScooterAdminController::class, 'rentals']);
        Route::post('scooter-rentals/{id}/confirm-payment', [ScooterAdminController::class, 'confirmPayment']);
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
        Route::get('wallet', [DriverWalletController::class, 'show']);
        Route::post('wallet/topup', [DriverWalletController::class, 'requestTopup']);
        Route::get('kyc', [DriverKycController::class, 'show']);
        Route::post('kyc', [DriverKycController::class, 'submit']);
        Route::get('lost-items', [LostItemController::class, 'index']);
        Route::post('lost-items/{id}/respond', [LostItemController::class, 'driverRespond']);
    });
    
    // Rotas para Clientes
    // Nota: lost-items do cliente fica sob /customer/* para não sobrescrever
    // a rota admin GET /lost-items (Laravel mantém só a última URI+método).
    Route::middleware('role:customer')->prefix('customer')->group(function () {
        Route::get('orders', function () {
            $orders = \App\Models\Order::where('user_id', auth()->id())
                ->with(['orderStatus', 'orderType', 'agent', 'vehicle'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json(['status' => 'success', 'data' => $orders]);
        });
        Route::post('lost-items', [LostItemController::class, 'store']);
        Route::get('lost-items', [LostItemController::class, 'index']);
    });

    // Kanguro Business (parceiro / dono de loja)
    Route::middleware('role:shop_owner')->prefix('business')->group(function () {
        Route::get('dashboard', [BusinessController::class, 'dashboard']);
        Route::get('earnings', [FinanceController::class, 'businessEarnings']);
        Route::get('orders', [BusinessController::class, 'orders']);
        Route::get('orders/{id}', [BusinessController::class, 'showOrder']);
        Route::post('orders/{id}/reject', [BusinessController::class, 'rejectOrder']);
        Route::post('orders/{id}/shop-accept', [OrderController::class, 'shopAccept']);
        Route::post('orders/{id}/shop-ready', [OrderController::class, 'shopReady']);
        Route::get('products', [BusinessController::class, 'products']);
        Route::post('products', [BusinessController::class, 'storeProduct']);
        Route::put('products/{id}', [BusinessController::class, 'updateProduct']);
        Route::post('products/{id}/sold-out', [BusinessController::class, 'markSoldOut']);
        Route::get('products/export', [BusinessController::class, 'exportProductsCsv']);
        Route::post('products/import', [BusinessController::class, 'importProductsCsv']);
        Route::get('products/{id}/movements', [BusinessController::class, 'productMovements']);
        Route::post('featured-request', [BusinessController::class, 'requestFeatured']);
        Route::get('catalog-options', function () {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'product_categories' => \App\Models\ProductCategory::query()->orderBy('name')->get(['id', 'name']),
                    'product_statuses' => \App\Models\ProductStatus::query()->orderBy('id')->get(['id', 'name']),
                ],
            ]);
        });

        Route::get('rooms', [BusinessController::class, 'roomTypes']);
        Route::post('rooms', [BusinessController::class, 'storeRoomType']);
        Route::put('rooms/{id}', [BusinessController::class, 'updateRoomType']);
        Route::get('hotel-bookings', [BusinessController::class, 'hotelBookings']);
        Route::get('hotel-bookings/{id}', [HotelController::class, 'show']);
        Route::post('hotel-bookings/{id}/confirm', [HotelController::class, 'confirm']);
        Route::post('hotel-bookings/{id}/reject', [HotelController::class, 'reject']);
        Route::post('hotel-bookings/{id}/check-in', [HotelController::class, 'checkIn']);
        Route::post('hotel-bookings/{id}/check-out', [HotelController::class, 'checkOut']);
        Route::post('hotel-bookings/{id}/no-show', [HotelController::class, 'noShow']);
        Route::post('hotel-bookings/{id}/cancel', [HotelController::class, 'cancel']);
        Route::get('rooms/{id}/blocks', [HotelController::class, 'roomBlocks']);
        Route::post('rooms/{id}/blocks', [HotelController::class, 'storeRoomBlock']);
        Route::delete('rooms/{id}/blocks/{blockId}', [HotelController::class, 'destroyRoomBlock']);

        Route::get('services', [BusinessController::class, 'serviceOfferings']);
        Route::post('services', [BusinessController::class, 'storeServiceOffering']);
        Route::put('services/{id}', [BusinessController::class, 'updateServiceOffering']);
        Route::get('service-bookings', [BusinessController::class, 'serviceBookings']);
        Route::get('service-bookings/{id}', [ServiceController::class, 'showBooking']);
        Route::post('service-bookings/{id}/accept', [ServiceController::class, 'accept']);
        Route::post('service-bookings/{id}/reject', [ServiceController::class, 'reject']);
        Route::post('service-bookings/{id}/start', [ServiceController::class, 'start']);
        Route::post('service-bookings/{id}/complete', [ServiceController::class, 'complete']);
    });
});
