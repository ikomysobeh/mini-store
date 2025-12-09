<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ColorController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SizeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\CartController;
use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\PayPalController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Laravel\Fortify\Features;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationPromptController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use Laravel\Fortify\Http\Controllers\VerifyEmailController;

// Redirect root to default locale (Arabic)
Route::get('/', function () {
    return redirect('/ar');
});

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => [ 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath' ]
    ], function(){ 

    // Fortify Authentication Routes with Locale Prefix
    $limiter = config('fortify.limiters.login');
    
    // Login routes
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->middleware(['guest:' . config('fortify.guard')])
        ->name('login');
    
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware(array_filter([
            'guest:' . config('fortify.guard'),
            $limiter ? 'throttle:' . $limiter : null,
        ]))
        ->name('login.store');
    
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
    
    // Registration routes
    if (Features::enabled(Features::registration())) {
        Route::get('/register', [RegisteredUserController::class, 'create'])
            ->middleware(['guest:' . config('fortify.guard')])
            ->name('register');
        
        Route::post('/register', [RegisteredUserController::class, 'store'])
            ->middleware(['guest:' . config('fortify.guard')])
            ->name('register.store');
    }
    
    // Password Reset routes
    if (Features::enabled(Features::resetPasswords())) {
        Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])
            ->middleware(['guest:' . config('fortify.guard')])
            ->name('password.request');
        
        Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
            ->middleware(['guest:' . config('fortify.guard')])
            ->name('password.email');
        
        Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
            ->middleware(['guest:' . config('fortify.guard')])
            ->name('password.reset');
        
        Route::post('/reset-password', [NewPasswordController::class, 'store'])
            ->middleware(['guest:' . config('fortify.guard')])
            ->name('password.update');
    }
    
    // Email Verification routes
    if (Features::enabled(Features::emailVerification())) {
        Route::get('/email/verify', [EmailVerificationPromptController::class, '__invoke'])
            ->middleware(['auth'])
            ->name('verification.notice');
        
        Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
            ->middleware(['auth', 'signed', 'throttle:6,1'])
            ->name('verification.verify');
        
        Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->middleware(['auth', 'throttle:6,1'])
            ->name('verification.send');
    }

    // Public routes
    Route::get('/', [HomeController::class, 'index'])->name('home');

    // Public Donation Routes
    Route::get('/donate', [\App\Http\Controllers\DonationController::class, 'index'])
        ->name('donation.form');
    Route::post('/donate', [\App\Http\Controllers\DonationController::class, 'store'])
        ->name('donation.store');
    Route::get('/donation/success', [\App\Http\Controllers\DonationController::class, 'success'])
        ->name('donation.success');
    Route::get('/donation/cancel', [\App\Http\Controllers\DonationController::class, 'cancel'])
        ->name('donation.cancel');

    // Products
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

    // Cart (guest accessible)

    // Checkout (requires auth - Fortify)
    Route::middleware(['auth'])->group(function () {
        Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
        Route::patch('/cart/update/{cartItem}', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/cart/remove/{cartItem}', [CartController::class, 'remove'])->name('cart.remove');
        Route::delete('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

        Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
        Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

        Route::get('/my-orders', [App\Http\Controllers\Web\UserOrderController::class, 'index'])->name('user.orders.index');
        Route::get('/my-orders/{order}', [App\Http\Controllers\Web\UserOrderController::class, 'show'])->name('user.orders.show');
        Route::post('/my-orders/{order}/reorder', [App\Http\Controllers\Web\UserOrderController::class, 'reorder'])->name('user.orders.reorder');

        Route::post('/my-orders/{order}/retry-payment', [App\Http\Controllers\Web\UserOrderController::class, 'retryPayment'])->name('orders.retry-payment');


        Route::post('/orders', [CheckoutController::class, 'store'])->name('orders.store');
        Route::post('/payments/paypal/create', [PayPalController::class, 'create'])->name('payments.paypal.create');
        Route::post('/payments/paypal/capture', [PayPalController::class, 'capture'])->name('payments.paypal.capture');

        Route::get('/order/success', [OrderController::class, 'success'])->name('orders.success');
        Route::get('/order/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    });

    // Orders (requires auth)
    //Route::middleware(['auth'])->group(function () {
    //    Route::get('/orders', [\App\Http\Controllers\Web\OrderController::class, 'index'])->name('orders.index');
    //    Route::get('/orders/{order}', [\App\Http\Controllers\Web\OrderController::class, 'show'])->name('orders.show');
    //});

    Route::get('/payment/success', [App\Http\Controllers\Web\OrderController::class, 'paymentSuccess'])->name('payment.success');
    Route::get('/payment/cancel', [App\Http\Controllers\Web\OrderController::class, 'paymentCancel'])->name('payment.cancel');

});


Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Users Management
    Route::resource('users', UserController::class);

    // Categories Management
    Route::resource('categories', CategoryController::class);

    Route::get('/products/export', [AdminProductController::class, 'export'])->name('products.export');
    Route::post('/products/export-selected', [AdminProductController::class, 'exportSelected'])->name('products.export-selected');
    Route::post('/products/bulk-delete', [AdminProductController::class, 'bulkDelete'])->name('products.bulk-delete');
    // Products Management
    Route::resource('products', AdminProductController::class);

    Route::get('/orders/export', [OrderController::class, 'export'])->name('orders.export');
    Route::post('/orders/export-selected', [OrderController::class, 'exportSelected'])->name('orders.export-selected');
    // Orders Management
    Route::resource('orders', OrderController::class)->except(['create', 'store', 'destroy']);


    Route::get('/donations', [\App\Http\Controllers\Admin\DonationController::class, 'index'])
        ->name('donations.index');
    Route::get('/donations/{donation}', [\App\Http\Controllers\Admin\DonationController::class, 'show'])
        ->name('donations.show');
    Route::delete('/donations/{donation}', [\App\Http\Controllers\Admin\DonationController::class, 'destroy'])
        ->name('donations.destroy');
    Route::post('/donations/bulk-delete', [\App\Http\Controllers\Admin\DonationController::class, 'bulkDelete'])
        ->name('donations.bulk-delete');

    // Colors Management
    Route::resource('colors', ColorController::class);
    Route::post('colors/bulk-action', [ColorController::class, 'bulkAction'])->name('colors.bulk-action');
    Route::post('colors/sort-order', [ColorController::class, 'updateSortOrder'])->name('colors.sort-order');

    // Sizes Management
    Route::resource('sizes', SizeController::class);
    Route::post('sizes/bulk-action', [SizeController::class, 'bulkAction'])->name('sizes.bulk-action');
    Route::post('sizes/sort-order', [SizeController::class, 'updateSortOrder'])->name('sizes.sort-order');

    // Product Variants Management
    Route::resource('product-variants', ProductVariantController::class)->except(['create', 'store']);
    Route::post('product-variants/bulk-action', [ProductVariantController::class, 'bulkAction'])->name('product-variants.bulk-action');
    Route::post('product-variants/bulk-stock', [ProductVariantController::class, 'bulkUpdateStock'])->name('product-variants.bulk-stock');
    Route::post('product-variants/generate', [ProductVariantController::class, 'generateVariants'])->name('product-variants.generate');

    // Settings
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\NotificationController::class, 'index'])
            ->name('index');

        // API endpoints
        Route::get('/count', [App\Http\Controllers\Admin\NotificationController::class, 'getUnreadCount'])
            ->name('count');
        Route::get('/recent', [App\Http\Controllers\Admin\NotificationController::class, 'getRecent'])
            ->name('recent');
        Route::post('/{id}/read', [App\Http\Controllers\Admin\NotificationController::class, 'markAsRead'])
            ->name('mark-read');
        Route::post('/mark-all-read', [App\Http\Controllers\Admin\NotificationController::class, 'markAllAsRead'])
            ->name('mark-all-read');
        Route::delete('/{id}', [App\Http\Controllers\Admin\NotificationController::class, 'destroy'])
            ->name('destroy');
        Route::post('/bulk-action', [App\Http\Controllers\Admin\NotificationController::class, 'bulkAction'])
            ->name('bulk-action');
    });

});
