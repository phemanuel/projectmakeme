<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\MakeMeParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MakeMeParticipantProfileController extends Controller
{
    public function index()
    {
        $participant = auth('makeme')->user();
        abort_unless($participant && $participant->status === 'active', 403);

        return view('makeme.participant.profile.index', compact('participant'));
    }

    public function update(Request $request)
    {
        $participant = auth('makeme')->user();
        abort_unless($participant && $participant->status === 'active', 403);

        // Contestants cannot alter their account identity, email, status, contestant
        // number, skill category, activation, wallet balance or permissions.
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'surname' => ['required', 'string', 'max:100'],
            'other_name' => ['nullable', 'string', 'max:100'],
            'mobile_no' => ['required', 'string', 'regex:/^[+0-9() .-]{7,25}$/'],
            'alternative_email' => ['nullable', 'email', 'max:255'],
            'business_name' => ['nullable', 'string', 'max:180'],
            'profession' => ['nullable', 'string', 'max:150'],
            'age' => ['nullable', 'integer', 'between:16,120'],
            'city' => ['nullable', 'string', 'max:120'],
            'state_of_origin' => ['nullable', 'string', 'max:120'],
            'instagram_handle' => ['nullable', 'string', 'max:150'],
            'tiktok_handle' => ['nullable', 'string', 'max:150'],
            'facebook_handle' => ['nullable', 'string', 'max:255'],
            'x_handle' => ['nullable', 'string', 'max:255'],
            'linkedin_handle' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        unset($data['photo']);
        $newPhoto = null;
        if ($request->hasFile('photo')) {
            $newPhoto = $request->file('photo')->store('makeme/contestants', 'public');
            if (!$newPhoto) {
                throw ValidationException::withMessages([
                    'photo' => 'Photograph upload failed. Please try again.',
                ]);
            }
            $data['photo_path'] = $newPhoto;
        }

        $oldPhoto = null;
        $changed = false;

        try {
            DB::transaction(function () use ($participant, $data, &$oldPhoto, &$changed) {
                $locked = MakeMeParticipant::query()
                    ->lockForUpdate()
                    ->findOrFail($participant->getKey());

                abort_unless($locked->status === 'active', 403);

                $oldPhoto = $locked->photo_path;
                $locked->fill($data);
                $dirtyFields = array_keys($locked->getDirty());
                $changed = !empty($dirtyFields);

                if (!$changed) {
                    return;
                }

                $locked->save();

                // Participant activity uses the participant as actor; never an admin guard.
                // Avoid logging personal information or raw uploaded file contents.
                activity('makeme-participants')
                    ->event('self_profile_updated')
                    ->causedBy($locked)
                    ->performedOn($locked)
                    ->withProperties([
                        'module' => 'participants',
                        'action' => 'self_profile_updated',
                        'participant_id' => $locked->getKey(),
                        'changed_fields' => $dirtyFields,
                        'ip_address' => request()->ip(),
                    ])
                    ->log('Contestant updated their profile');
            }, 3);
        } catch (\Throwable $e) {
            if ($newPhoto) {
                Storage::disk('public')->delete($newPhoto);
            }
            throw $e;
        }

        if ($changed && $newPhoto && $oldPhoto && $oldPhoto !== $newPhoto) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return redirect()
            ->route('makeme.participant.profile.index')
            ->with('success', $changed ? 'Your profile has been updated.' : 'No changes were made.');
    }
}
