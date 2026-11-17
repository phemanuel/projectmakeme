
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\CheckMakeMePermission;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        /*
        |--------------------------------------------------------------------------
        | Custom Middleware Aliases
        |--------------------------------------------------------------------------
        */

        $middleware->alias([
            'makeme.admin' => \App\Http\Middleware\EnsureMakeMeAdmin::class,
            'makeme.participant' => \App\Http\Middleware\EnsureMakeMeParticipant::class,
            'makeme.permission' => CheckMakeMePermission::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Redirect Unauthenticated Users
        |--------------------------------------------------------------------------
        |
        | Kreativs go to participant login.
        | Administrators go to admin login.
        |
        */

        $middleware->redirectGuestsTo(function (Request $request) {

            if ($request->is(
                'live/participant',
                'live/participant/*'
            )) {
                return route('makeme.participant.login');
            }

            return route('makeme.login');
        });

        /*
        |--------------------------------------------------------------------------
        | Redirect Already Authenticated Users
        |--------------------------------------------------------------------------
        |
        | Prevent logged-in Kreativs from being redirected
        | to the administrator login when they revisit
        | their participant login URL.
        |
        */

        $middleware->redirectUsersTo(function (Request $request) {

            if ($request->is(
                'live/participant',
                'live/participant/*',
                'live/invitation',
                'live/invitation/*'
            )) {
                return route('makeme.participant.dashboard');
            }

            return route('admin.makeme.dashboard');
        });

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
