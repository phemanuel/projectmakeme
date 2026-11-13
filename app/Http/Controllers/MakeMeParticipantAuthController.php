<?php

namespace App\Http\Controllers;

use App\Models\MakeMeParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MakeMeParticipantAuthController extends Controller
{
    public function showLogin()
    {
        return view('makeme.participant.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email'=>['required','email'], 'password'=>['required','string']]);
        $data['email'] = strtolower(trim($data['email']));
        $key = 'makeme-participant-login:'.sha1($data['email'].'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email'=>'Too many attempts. Please try again later.']);
        }
        // Never let an inactive/unverified participant start an authenticated session.
        $participant = MakeMeParticipant::where('email', $data['email'])->first();
        if (!$participant || $participant->status !== 'active' || !$participant->email_verified_at ||
            !$participant->password || !Hash::check($data['password'], $participant->password)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email'=>'Unable to sign in. Check your credentials or activate your account.']);
        }
        Auth::guard('makeme')->login($participant, $request->boolean('remember'));
        $request->session()->regenerate();
        RateLimiter::clear($key);
        $participant->forceFill(['last_login_at'=>now()])->save();
        return redirect()->route('makeme.participant.dashboard');
    }

    public function dashboard(Request $request)
    {
        return view('makeme.participant.dashboard', ['participant'=>$request->user('makeme')]);
    }

    public function logout(Request $request)
    {
        Auth::guard('makeme')->logout();
        // Both guards share Laravel's session. Invalidating logs out both, if logged in concurrently.
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('makeme.participant.login');
    }

    public function showReset(Request $request, string $token)
    {
        return view('makeme.participant.set-password', ['token'=>$token, 'email'=>$request->query('email', '')]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token'=>['required','string'], 'email'=>['required','email'],
            'password'=>['required','string','min:10','confirmed'],
        ]);
        $data['email'] = strtolower(trim($data['email']));
        $result = Password::broker('makeme_participants')->reset($data, function ($participant, $password) {
            $participant->forceFill([
                'password'=>$password,
                'email_verified_at'=>now(), // possession of reset invitation verifies this email
                'remember_token'=>Str::random(60),
            ])->save();
        });
        if ($result !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email'=>'This invitation has expired or is invalid. Contact the show administrator for a new one.']);
        }
        return redirect()->route('makeme.participant.login')->with('success', 'Password set. You can now sign in.');
    }
}
