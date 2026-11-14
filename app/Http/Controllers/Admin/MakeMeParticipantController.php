<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MakeMeParticipant;
use App\Services\MakeMeActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class MakeMeParticipantController extends Controller
{
    public const SKILLS = [
        'fashion_designing' => 'Fashion Designing',
        'shoe_making' => 'Shoe-making',
        'hair_styling' => 'Hair Styling',
        'photography' => 'Photography',
        'makeup_artistry' => 'Makeup Artistry',
        'modelling' => 'Modelling',
    ];

    private const PROFILE_FIELDS = [
        'first_name', 'surname', 'other_name', 'email', 'mobile_no',
        'skill_category', 'status', 'business_name', 'profession', 'age',
        'city', 'state_of_origin', 'alternative_email', 'instagram_handle',
        'tiktok_handle', 'facebook_handle', 'x_handle', 'linkedin_handle',
    ];

    private function filtered(Request $request)
    {
        return MakeMeParticipant::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = trim((string) $request->input('search'));
                $query->where(function ($q) use ($term) {
                    foreach (['participant_number', 'first_name', 'surname', 'email', 'mobile_no', 'business_name', 'profession'] as $column) {
                        $q->orWhere($column, 'like', '%' . $term . '%');
                    }
                });
            })
            ->when($request->filled('skill'), fn ($q) => $q->where('skill_category', $request->skill))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));
    }

    private function validateFilters(Request $request): void
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'skill' => ['nullable', Rule::in(array_keys(self::SKILLS))],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
    }

    public function index(Request $request)
    {
        $this->validateFilters($request);
        $participants = $this->filtered($request)->latest('id')->paginate(20)->withQueryString();
        $stats = [
            'total' => MakeMeParticipant::count(),
            'active' => MakeMeParticipant::where('status', 'active')->count(),
            'inactive' => MakeMeParticipant::where('status', 'inactive')->count(),
        ];
        $skills = self::SKILLS;
        return view('admin.makeme.participants.index', compact('participants', 'stats', 'skills'));
    }

    private function validatedProfile(Request $request, ?MakeMeParticipant $participant = null): array
    {
        $emailUnique = Rule::unique('makeme_participants', 'email');
        if ($participant) {
            $emailUnique->ignore($participant->getKey());
        }

        $rules = [
            'first_name' => ['required', 'string', 'max:100'],
            'surname' => ['required', 'string', 'max:100'],
            'other_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', $emailUnique],
            'mobile_no' => ['required', 'string', 'regex:/^[+0-9() .-]{7,25}$/'],
            'skill_category' => ['required', Rule::in(array_keys(self::SKILLS))],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'business_name' => ['nullable', 'string', 'max:180'],
            'profession' => ['nullable', 'string', 'max:150'],
            'age' => ['nullable', 'integer', 'between:1,120'],
            'city' => ['nullable', 'string', 'max:120'],
            'state_of_origin' => ['nullable', 'string', 'max:120'],
            'alternative_email' => ['nullable', 'email', 'max:255'],
            'instagram_handle' => ['nullable', 'string', 'max:150'],
            'tiktok_handle' => ['nullable', 'string', 'max:150'],
            'facebook_handle' => ['nullable', 'string', 'max:255'],
            'x_handle' => ['nullable', 'string', 'max:255'],
            'linkedin_handle' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ];

        $data = $request->validate($rules);
        unset($data['photo']);
        $data['email'] = strtolower(trim($data['email']));
        if (!empty($data['alternative_email'])) {
            $data['alternative_email'] = strtolower(trim($data['alternative_email']));
        }
        return $data;
    }

    
    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'surname' => ['required', 'string', 'max:100'],
            'other_name' => ['nullable', 'string', 'max:100'],

            'email' => [
                'required',
                'email',
                'max:255',
            ],

            'mobile_no' => [
                'required',
                'string',
                'regex:/^[+0-9() .-]{7,25}$/',
            ],

            'skill_category' => [
                'required',
                Rule::in(array_keys(self::SKILLS)),
            ],

            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],

            'business_name' => ['nullable', 'string', 'max:180'],
            'profession' => ['nullable', 'string', 'max:150'],
            'age' => ['nullable', 'integer', 'between:1,120'],
            'city' => ['nullable', 'string', 'max:120'],
            'state_of_origin' => ['nullable', 'string', 'max:120'],
            'alternative_email' => ['nullable', 'email', 'max:255'],

            'instagram_handle' => ['nullable', 'string', 'max:150'],
            'tiktok_handle' => ['nullable', 'string', 'max:150'],
            'facebook_handle' => ['nullable', 'string', 'max:255'],
            'x_handle' => ['nullable', 'string', 'max:255'],
            'linkedin_handle' => ['nullable', 'string', 'max:255'],

            'photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
            ],
        ]);

        $data['email'] = strtolower(trim($data['email']));

        $photo = $request->file('photo');
        unset($data['photo']);

        $result = DB::transaction(function () use ($data, $photo) {

            // Include soft-deleted records in the lookup.
            $participant = MakeMeParticipant::withTrashed()
                ->where('email', $data['email'])
                ->lockForUpdate()
                ->first();

            if ($participant && !$participant->trashed()) {
                throw ValidationException::withMessages([
                    'email' => 'This email is already registered to an active participant.',
                ]);
            }

            $photoPath = null;

            if ($photo) {
                $photoPath = $photo->store(
                    'makeme/contestants',
                    'public'
                );
            }

            try {

                if ($participant && $participant->trashed()) {

                    // Restore the existing contestant account.
                    $participant->restore();

                    $participant->fill($data);

                    if ($photoPath) {
                        $participant->photo_path = $photoPath;
                    }

                    if (
                        $participant->status === 'active' &&
                        !$participant->approved_at
                    ) {
                        $participant->approved_at = now();
                    }

                    $participant->save();

                    MakeMeActivityLogger::record(
                        'participants',
                        'restored',
                        'Deleted participant restored during registration',
                        $participant,
                        [
                            'participant_id' => $participant->id,
                            'participant_number' => $participant->participant_number,
                        ]
                    );

                    return 'restored';
                }

                // Create a completely new participant.
                $attributes = $data + [
                    'password' => null,
                    'approved_at' => $data['status'] === 'active'
                        ? now()
                        : null,
                ];

                if ($photoPath) {
                    $attributes['photo_path'] = $photoPath;
                }

                $participant = MakeMeParticipant::create($attributes);

                $participant->participant_number = 'MKL-' .
                    str_pad(
                        (string) $participant->id,
                        5,
                        '0',
                        STR_PAD_LEFT
                    );

                $participant->save();

                MakeMeActivityLogger::record(
                    'participants',
                    'created',
                    'Participant created',
                    $participant,
                    [
                        'participant_id' => $participant->id,
                        'participant_number' => $participant->participant_number,
                    ]
                );

                return 'created';

            } catch (\Throwable $e) {

                if ($photoPath) {
                    \Illuminate\Support\Facades\Storage::disk('public')
                        ->delete($photoPath);
                }

                throw $e;
            }

        }, 3);

        return redirect()
            ->route('admin.makeme.participants.index')
            ->with(
                'success',
                $result === 'restored'
                    ? 'Previously deleted participant restored successfully.'
                    : 'Participant created successfully.'
            );
    }

    public function update(Request $request, MakeMeParticipant $participant)
    {
        $data = $this->validatedProfile($request, $participant);
        $oldPhotoPath = $participant->photo_path;
        $newPhotoPath = null;

        try {
            if ($request->hasFile('photo')) {
                $newPhotoPath = $request->file('photo')->store('makeme/contestants', 'public');
            }

            DB::transaction(function () use ($participant, $data, $newPhotoPath) {
                $locked = MakeMeParticipant::query()->lockForUpdate()->findOrFail($participant->getKey());
                $before = $locked->only(self::PROFILE_FIELDS);
                $hadPhoto = (bool) $locked->photo_path;

                if ($data['email'] !== strtolower((string) $locked->email)) {
                    $data['email_verified_at'] = null;
                    $data['password'] = null;
                }
                if ($data['status'] === 'active' && !$locked->approved_at) {
                    $data['approved_at'] = now();
                }
                if ($newPhotoPath !== null) {
                    $data['photo_path'] = $newPhotoPath;
                }

                $locked->update($data);
                $after = $locked->fresh()->only(self::PROFILE_FIELDS);
                $changes = [];
                foreach ($before as $field => $oldValue) {
                    $newValue = $after[$field] ?? null;
                    if ($oldValue != $newValue) {
                        $changes[$field] = ['before' => $oldValue, 'after' => $newValue];
                    }
                }

                if ($changes || $newPhotoPath !== null) {
                    MakeMeActivityLogger::record('participants', 'updated', 'Participant profile updated', $locked, [
                        'participant_id' => $locked->getKey(),
                        'changes' => $changes,
                        'photo_changed' => $newPhotoPath !== null,
                        'had_previous_photo' => $hadPhoto,
                    ]);
                }
            }, 3);
        } catch (Throwable $e) {
            if ($newPhotoPath) Storage::disk('public')->delete($newPhotoPath);
            throw $e;
        }

        if ($newPhotoPath !== null && $oldPhotoPath && $oldPhotoPath !== $newPhotoPath) {
            // Best effort file cleanup AFTER database commit; never delete old image earlier.
            Storage::disk('public')->delete($oldPhotoPath);
        }

        return back()->with('success', 'Participant updated successfully.');
    }

    
    public function invite(MakeMeParticipant $participant)
    {
        // Only active participants can receive invitations.
        if ($participant->status !== 'active') {
            return back()->with(
                'error',
                'Activate this participant before sending an invitation.'
            );
        }

        // Do not resend activation invitations to verified accounts.
        if ($participant->email_verified_at) {
            return back()->with(
                'error',
                'This participant has already activated their account.'
            );
        }

        // Send a fresh activation link using the participant broker.
        $status = Password::broker('makeme_participants')
            ->sendResetLink([
                'email' => $participant->email,
            ]);

        if ($status === Password::RESET_LINK_SENT) {

            // Record successful invitation request.
            MakeMeActivityLogger::record(
                'participants',
                'invitation_sent',
                'Participant activation invitation sent',
                $participant,
                [
                    'participant_id' => $participant->id,
                    'participant_number' => $participant->participant_number,
                    'delivery_method' => 'email',
                ]
            );

            return back()->with(
                'success',
                'Activation invitation sent successfully to '
                . $participant->email
            );
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->with(
                'error',
                'An invitation was sent to this contestant recently. Please wait at least 60 seconds before resending.'
            );
        }
        return back()->with(
            'error',
            'Unable to send the invitation. Please check your email configuration.'
        );
    }


    public function export(Request $request): StreamedResponse
    {
        $this->validateFilters($request);
        $query = $this->filtered($request)->orderBy('id');

        // Record successful export generation, BEFORE returning the response.
        MakeMeActivityLogger::record('participants', 'exported', 'Participant CSV export prepared', null, [
            'format' => 'csv',
            'filters' => $request->only(['search', 'skill', 'status']),
            'rows' => (clone $query)->count(),
        ]);

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Participant Number', 'First Name', 'Surname', 'Other Name', 'Email', 'Phone',
                'Skill', 'Status', 'Account Activated', 'Business Name', 'Profession', 'Age',
                'City', 'State of Origin', 'Alternative Email', 'Instagram', 'TikTok',
                'Facebook', 'X', 'LinkedIn', 'Date Added',
            ]);
            foreach ($query->cursor() as $p) {
                $cells = [
                    $p->participant_number, $p->first_name, $p->surname, $p->other_name,
                    $p->email, $p->mobile_no, self::SKILLS[$p->skill_category] ?? $p->skill_category,
                    $p->status, $p->email_verified_at ? 'Yes' : 'No', $p->business_name,
                    $p->profession, $p->age, $p->city, $p->state_of_origin,
                    $p->alternative_email, $p->instagram_handle, $p->tiktok_handle,
                    $p->facebook_handle, $p->x_handle, $p->linkedin_handle,
                    $p->created_at?->format('Y-m-d H:i:s'),
                ];
                fputcsv($handle, array_map(static function ($v) {
                    $v = (string) ($v ?? '');
                    return preg_match('/^[\s\x00-\x1F]*[=+@-]/u', $v) ? "'" . $v : $v;
                }, $cells));
            }
            fclose($handle);
        }, 'makeme-participants-' . now()->format('Ymd-His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE PARTICIPANT (SOFT DELETE)
    |--------------------------------------------------------------------------
    */

    public function destroy(MakeMeParticipant $participant)
    {
        DB::transaction(function () use ($participant) {

            MakeMeActivityLogger::record(
                'participants',
                'deleted',
                'Participant removed from active records',
                $participant,
                [
                    'participant_id' => $participant->id,
                    'participant_number' => $participant->participant_number,
                    'participant_name' => $participant->full_name,
                ]
            );

            $participant->delete();
        });

        return redirect()
            ->route('admin.makeme.participants.index')
            ->with(
                'success',
                'Participant removed successfully.'
            );
    }

    public function generateActivationLink(MakeMeParticipant $participant)
    {
        $admin = auth('web')->user();

        abort_unless(
            $admin && (
                $admin->hasRole('Super Admin', 'web') ||
                $admin->can('makeme.participants.invite')
            ),
            403
        );

        if ($participant->status !== 'active') {
            return back()->with(
                'error',
                'Only active participants can receive activation links.'
            );
        }

        if (!empty($participant->password)) {
            return back()->with(
                'error',
                'This participant already has a password. Use the password reset process instead.'
            );
        }

        // Generate a token using the EXISTING participant password broker.
        $token = Password::broker('makeme_participants')
            ->createToken($participant);

        // IMPORTANT: Use the exact password setup route already
        // referenced by MakeMeInvitationNotification.
        $url = route('makeme.invitation.show', [
            'token' => $token,
            'email' => $participant->email,
        ]);

        MakeMeActivityLogger::record(
            'participants',
            'activation_link_generated',
            'Manual activation link generated by administrator',
            $participant,
            [
                'participant_id' => $participant->id,
                'participant_number' => $participant->participant_number,
                'delivery_method' => 'manual',
            ]
        );

        // Never record the token or activation URL in activity logs.
        return back()->with('activation_link', [
            'name' => $participant->full_name,
            'url' => $url,
        ]);
    }

    
    public function generatePasswordResetLink(MakeMeParticipant $participant)
    {
        $admin = auth('web')->user();

        abort_unless(
            $admin && (
                $admin->hasRole('Super Admin', 'web') ||
                $admin->can('makeme.participants.invite')
            ),
            403
        );

        if ($participant->status !== 'active') {
            return back()->with(
                'error',
                'Only active participants can reset their passwords.'
            );
        }

        if (empty($participant->password)) {
            return back()->with(
                'error',
                'This contestant has not created a password yet. Use Generate Activation Link instead.'
            );
        }

        $token = Password::broker('makeme_participants')
            ->createToken($participant);

        // Use the same token-based password page as existing invitations.
        $url = route('makeme.invitation.show', [
            'token' => $token,
            'email' => $participant->email,
        ]);

        MakeMeActivityLogger::record(
            'participants',
            'password_reset_link_generated',
            'Administrator generated a participant password reset link',
            $participant,
            [
                'participant_id' => $participant->id,
                'participant_number' => $participant->participant_number,
                'delivery_method' => 'manual',
            ]
        );

        return back()->with('activation_link', [
            'name' => $participant->full_name,
            'url' => $url,
            'type' => 'password_reset',
        ]);
    }



}
