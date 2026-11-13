<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMakeMeAdmin
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('signin');
        }

        // Restrict access to existing NYFEW admins
        if ((int) $user->user_type !== 1) {
            abort(403, 'You do not have permission to access this portal.');
        }

        return $next($request);
    }
}
