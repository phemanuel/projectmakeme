
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Authentication
use App\Http\Controllers\MakeMeLoginController;
use App\Http\Controllers\MakeMeParticipantAuthController;
use App\Http\Controllers\MakeMeInvitationAcceptanceController;

// Project MakeMe Administration
use App\Http\Controllers\Admin\MakeMeAdminDashboardController;
use App\Http\Controllers\Admin\MakeMeAdminModuleController;
use App\Http\Controllers\Admin\MakeMeParticipantController;
use App\Http\Controllers\Admin\MakeMeParticipantImportController;
use App\Http\Controllers\Admin\MakeMeProductController;

// Administrative User & Role Management
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminRoleController;


/*
|--------------------------------------------------------------------------
| PROJECT MAKEME - PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::redirect('/', '/signin')->name('home');


/*
|--------------------------------------------------------------------------
| ADMINISTRATOR LOGIN
|--------------------------------------------------------------------------
*/

Route::get('/signin', [
    MakeMeLoginController::class,
    'show'
])->name('makeme.login');

Route::post('/signin', [
    MakeMeLoginController::class,
    'login'
])
    ->middleware('throttle:10,1')
    ->name('makeme.login.submit');


/*
|--------------------------------------------------------------------------
| PASSWORD RECOVERY - TEMPORARY
|--------------------------------------------------------------------------
|
| This route remains temporary until password recovery
| functionality is implemented.
|
*/

Route::get('/forgot-password', function () {
    return redirect()
        ->route('makeme.login')
        ->with(
            'error',
            'Password recovery is not yet configured. Contact your administrator.'
        );
})->name('password.request');


/*
|--------------------------------------------------------------------------
| PARTICIPANT INVITATION & PASSWORD CREATION
|--------------------------------------------------------------------------
|
| Preserve the existing makeme authentication guard.
|
*/

Route::get('/live/invitation/{token}', [
    MakeMeInvitationAcceptanceController::class,
    'show'
])
    ->middleware('guest:makeme')
    ->name('makeme.invitation.show');

Route::post('/live/invitation', [
    MakeMeInvitationAcceptanceController::class,
    'store'
])
    ->middleware([
        'guest:makeme',
        'throttle:10,1',
    ])
    ->name('makeme.invitation.store');



/*
|--------------------------------------------------------------------------
| PARTICIPANT AUTHENTICATION, DASHBOARD & MARKETPLACE
|--------------------------------------------------------------------------
*/

Route::prefix('live/participant')
    ->name('makeme.participant.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Participant Login (Guest Only)
        |--------------------------------------------------------------------------
        */

        Route::middleware('guest:makeme')
            ->group(function () {

                Route::get('/', [
                    MakeMeParticipantAuthController::class,
                    'showLogin'
                ])->name('login');

                Route::post('/', [
                    MakeMeParticipantAuthController::class,
                    'login'
                ])
                    ->middleware('throttle:10,1')
                    ->name('login.submit');
            });

        /*
        |--------------------------------------------------------------------------
        | Protected Participant Routes
        |--------------------------------------------------------------------------
        */

        Route::middleware([
            'auth:makeme',
            'makeme.participant',
        ])->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Dashboard
            |--------------------------------------------------------------------------
            */

            Route::get('/dashboard', [
                MakeMeParticipantAuthController::class,
                'dashboard'
            ])->name('dashboard');

            /*
            |--------------------------------------------------------------------------
            | Creative Marketplace
            |--------------------------------------------------------------------------
            */

            Route::get('/marketplace', [
                \App\Http\Controllers\Participant\MakeMeMarketplaceController::class,
                'index'
            ])->name('marketplace.index');

            Route::get('/marketplace/{product}', [
                \App\Http\Controllers\Participant\MakeMeMarketplaceController::class,
                'show'
            ])->name('marketplace.show');

            Route::post('/marketplace/{product}/purchase', [
                \App\Http\Controllers\Participant\MakeMeMarketplaceController::class,
                'purchase'
            ])
                ->middleware('throttle:5,1')
                ->name('marketplace.purchase');

            /*
            |--------------------------------------------------------------------------
            | Participant Orders
            |--------------------------------------------------------------------------
            */

            Route::get('/orders', [
                \App\Http\Controllers\Participant\MakeMeParticipantOrderController::class,
                'index'
            ])->name('orders.index');

            Route::get('/orders/{order}', [
                \App\Http\Controllers\Participant\MakeMeParticipantOrderController::class,
                'show'
            ])->name('orders.show');

            /*
            |--------------------------------------------------------------------------
            | Kolo Moni
            |--------------------------------------------------------------------------
            */
            Route::get('/kolomoni', [\App\Http\Controllers\Participant\MakeMeParticipantKoloController::class, 'index'])
            ->name('kolo.index');


            /*
            |--------------------------------------------------------------------------
            | Participant Logout
            |--------------------------------------------------------------------------
            */

            Route::post('/logout', [
                MakeMeParticipantAuthController::class,
                'logout'
            ])->name('logout');

        });
    });



/*
|--------------------------------------------------------------------------
| ADMINISTRATIVE USER MANAGEMENT
|--------------------------------------------------------------------------
|
| Super Admin only.
|
| The controller also checks that the authenticated administrator
| has the Super Admin role.
|
*/

Route::prefix('admin/user-management')
    ->name('admin.users.')
    ->middleware([
        'auth:web',
        'makeme.admin',
        'makeme.permission:admin.users.view',
    ])
    ->group(function () {

        // List administrators
        Route::get('/', [
            AdminUserController::class,
            'index'
        ])->name('index');

        // Create administrator
        Route::post('/', [
            AdminUserController::class,
            'store'
        ])
            ->middleware('makeme.permission:admin.users.create')
            ->name('store');

        // Update administrator
        Route::put('/{adminUser}', [
            AdminUserController::class,
            'update'
        ])
            ->middleware('makeme.permission:admin.users.edit')
            ->name('update');

        // Activate / Deactivate administrator
        Route::patch('/{adminUser}/status', [
            AdminUserController::class,
            'toggleStatus'
        ])
            ->middleware('makeme.permission:admin.users.edit')
            ->name('status');
    });


/*
|--------------------------------------------------------------------------
| ROLES & PERMISSIONS MANAGEMENT
|--------------------------------------------------------------------------
|
| Super Admin only.
|
| All role changes are also validated and authorized
| inside AdminRoleController.
|
*/

Route::prefix('admin/role-management')
    ->name('admin.roles.')
    ->middleware([
        'auth:web',
        'makeme.admin',
        'makeme.permission:admin.roles.view',
    ])
    ->group(function () {

        // List roles
        Route::get('/', [
            AdminRoleController::class,
            'index'
        ])->name('index');

        // Create role
        Route::post('/', [
            AdminRoleController::class,
            'store'
        ])
            ->middleware('makeme.permission:admin.roles.create')
            ->name('store');

        // Update role and permissions
        Route::put('/{role}', [
            AdminRoleController::class,
            'update'
        ])
            ->middleware('makeme.permission:admin.roles.edit')
            ->name('update');

        // Delete unused custom role
        Route::delete('/{role}', [
            AdminRoleController::class,
            'destroy'
        ])
            ->middleware('makeme.permission:admin.roles.delete')
            ->name('destroy');
    });


/*
|--------------------------------------------------------------------------
| PROJECT MAKEME - ADMIN PORTAL
|--------------------------------------------------------------------------
|
| All administrative routes use the existing web guard.
|
| makeme.admin:
|   Validates administrative eligibility.
|
| makeme.permission:
|   Validates permission to access specific modules/actions.
|
*/

Route::prefix('admin/live')
    ->name('admin.makeme.')
    ->middleware([
        'auth:web',
        'makeme.admin',
    ])
    ->group(function () {

        /*
        |--------------------------------------------------------------
        | DASHBOARD / OVERVIEW
        |--------------------------------------------------------------
        */

        Route::get('/', [
            MakeMeAdminDashboardController::class,
            'index'
        ])
            ->middleware('makeme.permission:makeme.dashboard.view')
            ->name('dashboard');


        /*
        |--------------------------------------------------------------
        | PARTICIPANT MANAGEMENT
        |--------------------------------------------------------------
        */

        // View participants
        Route::get('/participants', [
            MakeMeParticipantController::class,
            'index'
        ])
            ->middleware('makeme.permission:makeme.participants.view')
            ->name('participants.index');


        // Create participant
        Route::post('/participants', [
            MakeMeParticipantController::class,
            'store'
        ])
            ->middleware('makeme.permission:makeme.participants.create')
            ->name('participants.store');


        // Export participant records to Excel
        Route::get('/participants/export', [
            MakeMeParticipantController::class,
            'export'
        ])
            ->middleware('makeme.permission:makeme.participants.export')
            ->name('participants.export');


        /*
        |--------------------------------------------------------------
        | PARTICIPANT EXCEL IMPORT
        |--------------------------------------------------------------
        */

        // Download import template
        Route::get('/participants/import/template', [
            MakeMeParticipantImportController::class,
            'template'
        ])
            ->middleware('makeme.permission:makeme.participants.import')
            ->name('participants.import.template');


        // Preview uploaded Excel records
        Route::post('/participants/import/preview', [
            MakeMeParticipantImportController::class,
            'preview'
        ])
            ->middleware('makeme.permission:makeme.participants.import')
            ->name('participants.import.preview');


        // Confirm participant import
        Route::post('/participants/import/confirm', [
            MakeMeParticipantImportController::class,
            'confirm'
        ])
            ->middleware('makeme.permission:makeme.participants.import')
            ->name('participants.import.confirm');


        /*
        |--------------------------------------------------------------
        | PARTICIPANT UPDATES & INVITATIONS
        |--------------------------------------------------------------
        */

        // Update participant
        Route::patch('/participants/{participant}', [
            MakeMeParticipantController::class,
            'update'
        ])
            ->middleware('makeme.permission:makeme.participants.edit')
            ->name('participants.update');


        // Send participant invitation
        Route::post('/participants/{participant}/invite', [
            MakeMeParticipantController::class,
            'invite'
        ])
            ->middleware([
                'makeme.permission:makeme.participants.invite',
                'throttle:5,1',
            ])
            ->name('participants.invite');


        /*
        |--------------------------------------------------------------
        | PRODUCTS & INVENTORY MANAGEMENT
        |--------------------------------------------------------------
        |
        | Explicit resource routes allow us to assign
        | permissions to each action individually.
        |
        */
        
        // Products & Stock

        Route::get('/products', [
            MakeMeProductController::class, 'index'
        ])->middleware('makeme.permission:makeme.products.view')
        ->name('products.index');

        Route::get('/products/create', [
            MakeMeProductController::class, 'create'
        ])->middleware('makeme.permission:makeme.products.create')
        ->name('products.create');

        Route::post('/products', [
            MakeMeProductController::class, 'store'
        ])->middleware('makeme.permission:makeme.products.create')
        ->name('products.store');

        Route::get('/products/{product}/edit', [
            MakeMeProductController::class, 'edit'
        ])->middleware('makeme.permission:makeme.products.edit')
        ->name('products.edit');

        Route::get('/products/{product}', [
            MakeMeProductController::class, 'show'
        ])->middleware('makeme.permission:makeme.products.view')
        ->name('products.show');

        Route::match(['put', 'patch'], '/products/{product}', [
            MakeMeProductController::class, 'update'
        ])->middleware('makeme.permission:makeme.products.edit')
        ->name('products.update');

        Route::post('/products/{product}/stock', [
            MakeMeProductController::class, 'adjustStock'
        ])->middleware('makeme.permission:makeme.products.stock.adjust')
        ->name('products.stock.adjust');

        Route::delete('/products/{product}', [
            MakeMeProductController::class, 'destroy'
        ])->middleware('makeme.permission:makeme.products.delete')
        ->name('products.destroy');



        /*
        |--------------------------------------------------------------
        | WALLET MANAGEMENT
        |--------------------------------------------------------------
        |
        | Only the wallet listing exists in the supplied routes.
        | Financial adjustment routes must be secured separately
        | when they are implemented.
        |
        */


        Route::get('/wallets', [
            \App\Http\Controllers\Admin\MakeMeWalletController::class,
            'index'
        ])
            ->middleware(
                'makeme.permission:makeme.wallets.view'
            )
            ->name('wallets.index');


        Route::post('/wallets/{participant}/adjust', [
            \App\Http\Controllers\Admin\MakeMeWalletController::class,
            'adjust'
        ])
            ->middleware(
                'makeme.permission:makeme.wallets.adjust'
            )
            ->name('wallets.adjust');




        /*
        |--------------------------------------------------------------
        | ORDER MANAGEMENT
        |--------------------------------------------------------------
        |
        | Only the order listing exists currently.
        |
        */

       Route::get('/orders', [\App\Http\Controllers\Admin\MakeMeOrderController::class, 'index'])
            ->middleware('makeme.permission:makeme.orders.view')
            ->name('orders.index');

        Route::post('/orders', [\App\Http\Controllers\Admin\MakeMeOrderController::class, 'store'])
            ->middleware('makeme.permission:makeme.orders.create')
            ->name('orders.store');

        Route::post('/orders/{order}/fulfill', [\App\Http\Controllers\Admin\MakeMeOrderController::class, 'fulfill'])
            ->middleware('makeme.permission:makeme.orders.update-status')
            ->name('orders.fulfill');

        Route::post('/orders/{order}/cancel', [\App\Http\Controllers\Admin\MakeMeOrderController::class, 'cancel'])
            ->middleware('makeme.permission:makeme.orders.update-status')
            ->name('orders.cancel');



        /*
        |--------------------------------------------------------------
        | REPORTS & ANALYTICS
        |--------------------------------------------------------------
        |
        | Report export routes should require
        | makeme.reports.export when implemented.
        |
        */

        Route::get('/reports', [\App\Http\Controllers\Admin\MakeMeReportController::class, 'index'])
            ->middleware('makeme.permission:makeme.reports.view')
            ->name('reports.index');

        Route::get('/reports/export', [\App\Http\Controllers\Admin\MakeMeReportController::class, 'export'])
            ->middleware('makeme.permission:makeme.reports.export')
            ->name('reports.export');


    });


/*
|--------------------------------------------------------------------------
| ADMINISTRATOR LOGOUT
|--------------------------------------------------------------------------
|
| Preserve the existing web guard and route name.
|
*/

Route::post('/live/logout', function (Request $request) {

    Auth::guard('web')->logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('makeme.login');

})
    ->middleware('auth:web')
    ->name('makeme.logout');
