<?php

namespace App\Http\Controllers;

use App\Models\MakeMeParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;

class MakeMeInvitationAcceptanceController extends Controller
{
    // Display password setup form
    public function show(Request $request, string $token)
    {
        $email = $request->query('email');

        if (!$email) {
            abort(403, 'Invalid invitation link.');
        }

        $participant = MakeMeParticipant::where('email', $email)
            ->first();

        if (
            !$participant ||
            !Password::broker('makeme_participants')
                ->tokenExists($participant, $token)
        ) {
            return response()->view(
                'makeme.auth.invitation-expired',
                [],
                403
            );
        }

        return view('makeme.auth.create-password', [
            'token' => $token,
            'email' => $email,
            'participant' => $participant,
        ]);
    }

    // Save participant password
    public function store(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $status = Password::broker('makeme_participants')
            ->reset(
                $request->only(
                    'email',
                    'token',
                    'password',
                    'password_confirmation'
                ),
                function (MakeMeParticipant $participant, string $password) {

                    $participant->forceFill([
                        'password' => Hash::make($password),
                        'email_verified_at' => now(),
                    ]);

                    $participant->setRememberToken(
                        Str::random(60)
                    );

                    $participant->save();

                    event(new PasswordReset($participant));
                }
            );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('makeme.participant.login')
                ->with(
                    'success',
                    'Your password has been created successfully. You can now sign in.'
                );
        }

        return back()
            ->withErrors([
                'email' => 'This invitation link is invalid or has expired. Please request a new invitation.'
            ]);
    }
}
