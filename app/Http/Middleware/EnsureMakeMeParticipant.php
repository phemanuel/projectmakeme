<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMakeMeParticipant
{
    public function handle(Request $request, Closure $next): Response
    {
        $participant = $request->user('makeme');
        if (!$participant) return redirect()->route('makeme.participant.login');
        abort_unless($participant->status === 'active' && $participant->email_verified_at !== null, 403);
        return $next($request);
    }
}
