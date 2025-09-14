<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerOrderController;
use App\Http\Controllers\Api\AdminOrderController;
use App\Http\Controllers\Api\EmployerController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\Api\AdminEmployerController;

// -----------------------------
// Public routes (no authentication required)
// -----------------------------
Route::post('/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [UserController::class, 'register']);

// Public API endpoints
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/stores', [StoreController::class, 'index']);
Route::get('/stores/{id}', [StoreController::class, 'show']);
Route::get('/stores/category/{categoryId}', [StoreController::class, 'getByCategory']);
Route::get('/products/store/{storeId}', [ProductController::class, 'getByStore']);
Route::get('/products/store/{storeId}/categories', [ProductController::class, 'getStoreCategories']);

// ✅ PUBLIC HEALTH CHECK ENDPOINT — NO AUTH REQUIRED
Route::get('/test', function () {
    return response()->json([
        'message' => 'API IS WORKING!',
        'app_url' => env('APP_URL'),
        'app_env' => env('APP_ENV'),
    ]);
});

// -----------------------------
// Protected routes (auth:sanctum required)
// -----------------------------
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);

    // User profile
    Route::prefix('user')->group(function () {
        Route::get('/', [UserController::class, 'show']);
        Route::match(['post','put'], '/', [UserController::class, 'update']);
    });

    // Cart
    Route::post('/customer/cart/add', [CartController::class, 'addToCart']);
    Route::get('/customer/cart', [CartController::class, 'getCart']);
    Route::delete('/customer/cart', [CartController::class, 'clearCart']);

    // Customer Orders
    Route::prefix('customer')->group(function () {
        Route::post('/orders', [CustomerOrderController::class, 'store']);
        Route::get('/orders', [CustomerOrderController::class, 'index']);
        Route::get('/orders/{id}', [CustomerOrderController::class, 'show']);
        Route::patch('/orders/{id}/status', [CustomerOrderController::class, 'updateStatus']);
        Route::post('/orders/confirm', [CustomerOrderController::class, 'confirm']);
        Route::post('/orders/{id}/rate', [CustomerOrderController::class, 'rateOrder']);
    });

    // Employer
    Route::prefix('employer')->group(function () {
        Route::get('/orders', [EmployerController::class, 'myOrders']);
        Route::put('/orders/{id}/status', [EmployerController::class, 'updateOrderStatus']);
    });

    // Admin
    Route::prefix('admin')->group(function () {
        Route::get('/admin/notifications', [AdminController::class, 'getAdminNotifications']);
        Route::get('/admin/orders/status-changes', [AdminOrderController::class, 'getStatusChanges']);
        Route::get('/admin/users/new-registrations', [UserController::class, 'getNewRegistrations']);

        // Users management
        Route::get('/users', [AdminController::class, 'listUsers']);
        Route::get('/users/{id}', [AdminController::class, 'showUser']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
        Route::get('/users/new-registrations', [AdminController::class, 'newRegistrations']);

        // Orders
        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::get('/orders/{id}', [AdminOrderController::class, 'show']);
        Route::put('/orders/{id}/assign', [AdminOrderController::class, 'assignEmployer']);
        Route::put('/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);
        Route::delete('/orders/{id}', [AdminOrderController::class, 'destroy']);
        Route::get('/orders/updates', [AdminOrderController::class, 'getOrderUpdates']);

        // Categories
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

        // Stores
        Route::get('/stores', [StoreController::class, 'index']);
        Route::get('/stores/{id}', [StoreController::class, 'show']);
        Route::post('/stores', [StoreController::class, 'store']);
        Route::put('/stores/{id}', [StoreController::class, 'update']);
        Route::patch('/stores/{id}', [StoreController::class, 'update']);
        Route::delete('/stores/{id}', [StoreController::class, 'destroy']);

        // Store Categories
        Route::post('/store-categories', [StoreController::class, 'addStoreCategory']);
        Route::put('/store-categories/{id}', [StoreController::class, 'updateStoreCategory']);
        Route::delete('/store-categories/{id}', [StoreController::class, 'deleteStoreCategory']);
        Route::get('/stores/{storeId}/categories', [StoreController::class, 'getCategories']);

        // Products
        Route::get('/products', [ProductController::class, 'index']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);

        // Employers
        Route::prefix('employers')->group(function () {
            Route::get('/', [AdminEmployerController::class, 'index']);
            Route::put('/{id}/status', [AdminEmployerController::class, 'updateStatus']);
            Route::delete('/{id}', [AdminEmployerController::class, 'destroy']);
        });

        // Upload image for stores
        Route::post('/upload-image', function (Request $request) {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
            $path = $request->file('image')->store('stores', 'public');
            return response()->json(['path' => $path], 200);
        });
    });

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);

    // Delete user (protected by sanctum)
    Route::delete('/user', [UserController::class, 'destroy']);
});

// ✅ FALLOVER ROUTE — CLEAN AND SIMPLE
