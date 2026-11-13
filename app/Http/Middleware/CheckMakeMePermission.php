<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMakeMePermission
{
    public function handle(
        Request $request,
        Closure $next,
        string $permission
    ): Response {
        $user = $request->user('web');

        if (!$user) {
            return redirect()->route('makeme.login');
        }

        // Require an active administrative account.
        abort_unless(
            (int) $user->user_type === 1 &&
            (int) $user->user_status === 1 &&
            $user->applicant_type !== 'WAITLIST',
            403,
            'You do not have administrative access.'
        );

        // Super Admin is granted access to MakeMe modules.
        if ($user->hasRole('Super Admin', 'web')) {
            return $next($request);
        }

        abort_unless(
            $user->can($permission),
            403,
            'You do not have permission to perform this action.'
        );

        return $next($request);
    }
}
