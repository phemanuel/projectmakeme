<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\MakeMeParticipantController;
use App\Http\Controllers\MakeMeLoginController;
use App\Http\Controllers\Admin\MakeMeAdminModuleController;
use App\Http\Controllers\Admin\MakeMeAdminDashboardController;
use App\Http\Controllers\Admin\MakeMeProductController;
use App\Http\Controllers\Admin\MakeMeParticipantImportController;
use App\Http\Controllers\MakeMeParticipantAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


    //------Reset Password Route
    Route::get('/forgot-password', function () {
        return view('auth.forgot-password');
    })->middleware('guest')->name('password.request');
    Route::post('/forgot-password', [CustomForgotPasswordController::class, 'forgotPassword'])
        ->middleware('guest')
        ->name('password.email');
    Route::get('/reset-password/{token}/{email}', function ($token,$email) {
        return view('auth.user-reset-password', ['token' => $token , 'email' => $email]);
    })->middleware('guest')->name('password.reset');
    Route::post('/reset-password', [CustomForgotPasswordController::class, 'resetPassword'])
        ->middleware('guest')
        ->name('password.update');

    /*
    |--------------------------------------------------------------------------
    | PROJECT MAKEME LIVE - AUTHENTICATION
    |--------------------------------------------------------------------------
    */

    Route::get('/', [
        MakeMeLoginController::class, 'show'
    ])->name('makeme.login');

    Route::get('/', [
        MakeMeLoginController::class, 'show'
    ])->name('home');

    Route::get('/signin', [
        MakeMeLoginController::class, 'show'
    ])->name('makeme.login');


    Route::post('/signin', [
        MakeMeLoginController::class, 'login'
    ])->name('makeme.login.submit');


    Route::prefix('makeme-live/participant')->name('makeme.participant.')->group(function () {
        Route::get('/signin', [MakeMeParticipantAuthController::class, 'showLogin'])->name('login');
        Route::post('/signin', [MakeMeParticipantAuthController::class, 'login'])->name('login.submit');
        Route::get('/activate/{token}', [MakeMeParticipantAuthController::class, 'showReset'])->name('password.reset');
        Route::post('/activate', [MakeMeParticipantAuthController::class, 'reset'])->middleware('throttle:10,1')->name('password.update');
        Route::middleware(['auth:makeme', 'makeme.participant'])->group(function () {
            Route::get('/dashboard', [MakeMeParticipantAuthController::class, 'dashboard'])->name('dashboard');
            Route::post('/logout', [MakeMeParticipantAuthController::class, 'logout'])->name('logout');
        });
    });
    /*
    |--------------------------------------------------------------------------
    | PROJECT MAKEME LIVE - PARTICIPANT PORTAL
    |--------------------------------------------------------------------------
    */

    Route::prefix('makeme-live')
        ->name('makeme.')
        ->middleware(['auth', 'makeme.participant'])
        ->group(function () {

        // Dashboard
        Route::get('/dashboard', [
            MakeMeDashboardController::class, 'index'
        ])->name('dashboard');

        // Shop
        Route::get('/shop', [
            MakeMeShopController::class, 'index'
        ])->name('shop');

        Route::get('/shop/{product}', [
            MakeMeShopController::class, 'show'
        ])->name('shop.show');

        // Cart
        Route::get('/cart', [
            MakeMeCartController::class, 'index'
        ])->name('cart');

        Route::post('/cart/add', [
            MakeMeCartController::class, 'add'
        ])->name('cart.add');

        Route::patch('/cart/{product}', [
            MakeMeCartController::class, 'update'
        ])->name('cart.update');

        Route::delete('/cart/{product}', [
            MakeMeCartController::class, 'remove'
        ])->name('cart.remove');

        // Checkout
        Route::post('/checkout', [
            MakeMeOrderController::class, 'checkout'
        ])->name('checkout');

        // Wallet
        Route::get('/wallet', [
            MakeMeWalletController::class, 'index'
        ])->name('wallet');

        // Orders
        Route::get('/orders', [
            MakeMeOrderController::class, 'index'
        ])->name('orders');

        Route::get('/orders/{order}', [
            MakeMeOrderController::class, 'show'
        ])->name('orders.show');
       

    });


    /*
    |--------------------------------------------------------------------------
    | PROJECT MAKEME LIVE - ADMIN PORTAL
    |--------------------------------------------------------------------------
    */

    Route::prefix('admin/makeme-live')
        ->name('admin.makeme.')
        ->middleware(['auth', 'makeme.admin'])
        ->group(function () {

            // Dashboard
            Route::get('/', [
                MakeMeAdminDashboardController::class, 'index'
            ])->name('dashboard');

            // Products
            Route::resource('products',
                MakeMeProductController::class
            );

            // Product categories
            Route::resource('categories',
                MakeMeCategoryController::class
            )->except(['show']);

            // Participants
            Route::get('/participants', [MakeMeParticipantController::class, 'index'])
            ->name('participants.index');
            Route::post('/participants', [MakeMeParticipantController::class, 'store'])
            ->name('participants.store');
            Route::get('/participants/export', [MakeMeParticipantController::class, 'export'])
            ->name('participants.export');
            Route::get('/participants/import/template', [MakeMeParticipantImportController::class, 'template'])
            ->name('participants.import.template');
            Route::post('/participants/import/preview', [MakeMeParticipantImportController::class, 'preview'])
            ->name('participants.import.preview');
            Route::post('/participants/import/confirm', [MakeMeParticipantImportController::class, 'confirm'])
            ->name('participants.import.confirm');
            Route::patch('/participants/{participant}', [MakeMeParticipantController::class, 'update'])
            ->name('participants.update');
            Route::post('/participants/{participant}/invite', [MakeMeParticipantController::class, 'invite'])
            ->middleware('throttle:5,1')->name('participants.invite');

            // Credit allocation
            Route::post('/wallets/credit', [
                MakeMeWalletManagementController::class, 'credit'
            ])->name('wallets.credit');

            Route::post('/wallets/bulk-credit', [
                MakeMeWalletManagementController::class, 'bulkCredit'
            ])->name('wallets.bulk-credit');

            // Order management
            Route::get('/orders', [
                MakeMeOrderManagementController::class, 'index'
            ])->name('orders');

            Route::get('/orders/{order}', [
                MakeMeOrderManagementController::class, 'show'
            ])->name('orders.show');

            Route::patch('/orders/{order}/status', [
                MakeMeOrderManagementController::class, 'updateStatus'
            ])->name('orders.status');

            // Collection verification
            Route::post('/orders/{order}/collect', [
                MakeMeCollectionController::class, 'collect'
            ])->name('orders.collect');

            // Reports
            Route::get('/reports', [
                MakeMeReportController::class, 'index'
            ])->name('reports');

             Route::get('/participants', [
                MakeMeParticipantController::class, 'index'
            ])->name('participants.index');

            Route::get('/participants/create', [
                MakeMeParticipantController::class, 'create'
            ])->name('participants.create');

            Route::post('/participants', [
                MakeMeParticipantController::class, 'store'
            ])->name('participants.store');

            Route::get('/participants/import/template', [
                MakeMeParticipantImportController::class, 'template'
            ])->name('participants.import.template');

            Route::post('/participants/import/preview', [
                MakeMeParticipantImportController::class, 'preview'
            ])->name('participants.import.preview');

            Route::post('/participants/import/confirm', [
                MakeMeParticipantImportController::class, 'confirm'
            ])->name('participants.import.confirm');

            Route::get('/participants/export', [
                MakeMeParticipantController::class, 'export'
            ])->name('participants.export');

            Route::get('/participants/{participant}', [
                MakeMeParticipantController::class, 'show'
            ])->name('participants.show');

            Route::patch('/participants/{participant}', [
                MakeMeParticipantController::class, 'update'
            ])->name('participants.update');


            Route::get('/wallets', [
                MakeMeAdminModuleController::class, 'wallets'
            ])->name('wallets.index');

            Route::get('/orders', [
                MakeMeAdminModuleController::class, 'orders'
            ])->name('orders.index');

            Route::get('/reports', [
                MakeMeAdminModuleController::class, 'reports'
            ])->name('reports.index');

        });


        Route::post('/makeme-live/logout', function (Request $request) {

            Auth::guard('web')->logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            return redirect()->route('makeme.login');

        })->middleware('auth')->name('makeme.logout');


