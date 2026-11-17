<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MakeMeLoginController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            $destination = $this->destination(Auth::user());
            if ($destination !== null) {
                return redirect()->route($destination);
            }
        }

        return view('makeme.auth.signin');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = Str::lower(trim($credentials['email']));
        $key = 'makeme-signin:' . sha1($email . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Too many sign-in attempts. Try again in {$seconds} seconds.",
            ]);
        }

        // This uses the SAME web guard and users table as the original NYFEW site.
        if (!Auth::attempt(['email' => $email, 'password' => $credentials['password']], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $user = Auth::user();
        $destination = $this->destination($user);

        if ($destination === null) {
            // Never leave an ineligible/unverified person authenticated.
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => 'This account does not currently have access to Project MakeMe Live.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->route($destination);
    }

    
    private function destination(User $user): ?string
    {
        // Check basic account eligibility
        if (
            (int) $user->user_status !== 1 ||
            $user->applicant_type === 'WAITLIST'
        ) {
            return null;
        }

        // ADMINISTRATOR ACCESS
        if ((int) $user->user_type === 1) {

            // Super Admin
            if ($user->hasRole('Super Admin', 'web')) {
                return 'admin.makeme.dashboard';
            }

            // Dashboard access
            if ($user->can('makeme.dashboard.view')) {
                return 'admin.makeme.dashboard';
            }

            // Participant Management
            if ($user->can('makeme.participants.view')) {
                return 'admin.makeme.participants.index';
            }

            // Products & Stock
            if ($user->can('makeme.products.view')) {
                return 'admin.makeme.products.index';
            }

            // Kolo Money / Wallet Management
            if ($user->can('makeme.wallets.view')) {
                return 'admin.makeme.wallets.index';
            }

            // Order Management
            if ($user->can('makeme.orders.view')) {
                return 'admin.makeme.orders.index';
            }

            // Reports & Analytics
            if ($user->can('makeme.reports.view')) {
                return 'admin.makeme.reports.index';
            }

            // Administrators with no portal permissions
            return null;
        }

        // EXISTING PARTICIPANT ACCESS
        if (
            (int) $user->email_verified_status === 1 &&
            $user->makeMeParticipant()
                ->where('status', 'active')
                ->exists()
        ) {
            return 'user-dashboard';
        }

        return null;
    }

}
