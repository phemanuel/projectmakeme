<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MakeMeParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\MakeMeActivityLogger;

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

    private function filtered(Request $request)
    {
        return MakeMeParticipant::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = trim((string) $request->input('search'));
                $query->where(function ($q) use ($term) {
                    foreach (['participant_number','first_name','surname','email','mobile_no'] as $column) {
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
            'status' => ['nullable', Rule::in(['active','inactive'])],
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
        return view('admin.makeme.participants.index', compact('participants','stats','skills'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required','string','max:100'],
            'surname' => ['required','string','max:100'],
            'other_name' => ['nullable','string','max:100'],
            'email' => ['required','email','max:255','unique:makeme_participants,email'],
            'mobile_no' => ['required','string','regex:/^[+0-9() .-]{7,25}$/'],
            'skill_category' => ['required', Rule::in(array_keys(self::SKILLS))],
            'status' => ['required', Rule::in(['active','inactive'])],
        ]);
        $data['email'] = strtolower(trim($data['email']));
        DB::transaction(function () use ($data) {
            $participant = MakeMeParticipant::create($data + [
                'approved_at' => $data['status'] === 'active' ? now() : null,
                'password' => null, // activation via password reset broker, never a shared password
            ]);
            $participant->participant_number = 'MKL-' . str_pad((string) $participant->id, 5, '0', STR_PAD_LEFT);
            $participant->save();

            MakeMeActivityLogger::record(
                'participants',
                'created',
                'Participant created',
                $participant,
                [
                    'participant_id' => $participant->getKey(),
                ]
            );
        });
        return redirect()->route('admin.makeme.participants.index')->with('success', 'Participant created. Send an activation link after configuring email delivery.');
    }

    public function update(Request $request, MakeMeParticipant $participant)
    {
        $data = $request->validate([
            'first_name' => ['required','string','max:100'],
            'surname' => ['required','string','max:100'],
            'other_name' => ['nullable','string','max:100'],
            'email' => ['required','email','max:255',Rule::unique('makeme_participants','email')->ignore($participant->id)],
            'mobile_no' => ['required','string','regex:/^[+0-9() .-]{7,25}$/'],
            'skill_category' => ['required',Rule::in(array_keys(self::SKILLS))],
            'status' => ['required',Rule::in(['active','inactive'])],
        ]);
        $data['email'] = strtolower(trim($data['email']));

        $before = $participant->only([
            'first_name',
            'surname',
            'other_name',
            'email',
            'status',
        ]);
        // Changing email invalidates prior verification and any password reset token.
        if ($data['email'] !== strtolower((string) $participant->email)) {
            $data['email_verified_at'] = null;
            $data['password'] = null;
        }
        if ($data['status'] === 'active' && !$participant->approved_at) $data['approved_at'] = now();
        $participant->update($data);

        $after = $participant->fresh()->only(array_keys($before));

        $changes = [];

        foreach ($before as $field => $oldValue) {
            $newValue = $after[$field] ?? null;

            if ($oldValue != $newValue) {
                $changes[$field] = [
                    'before' => $oldValue,
                    'after' => $newValue,
                ];
            }
        }

        if (!empty($changes)) {
            MakeMeActivityLogger::record(
                'participants',
                'updated',
                'Participant information updated',
                $participant,
                [
                    'participant_id' => $participant->getKey(),
                    'changes' => $changes,
                ]
            );
        }
        return back()->with('success', 'Participant updated successfully.');
    }

    public function invite(MakeMeParticipant $participant)
    {
        if ($participant->status !== 'active') return back()->withErrors(['invite'=>'Activate the participant before sending an invitation.']);
        $status = Password::broker('makeme_participants')->sendResetLink(['email' => $participant->email]);
        return back()->with($status === Password::RESET_LINK_SENT ? 'success' : 'error',
            $status === Password::RESET_LINK_SENT ? 'Activation email sent.' : 'Email could not be sent. Check your mail configuration and logs.');

        MakeMeActivityLogger::record(
            'participants',
            'invitation_sent',
            'Participant invitation sent',
            $participant,
            [
                'participant_id' => $participant->getKey(),
                'delivery_method' => 'email',
            ]
        );
    }

    public function export(Request $request): StreamedResponse
    {
        $this->validateFilters($request);
        $query = $this->filtered($request)->orderBy('id');
        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Participant Number','First Name','Surname','Other Name','Email','Phone','Skill','Status','Account Activated','Date Added']);
            foreach ($query->cursor() as $p) {
                $cells = [
                    $p->participant_number, $p->first_name, $p->surname, $p->other_name,
                    $p->email, $p->mobile_no, self::SKILLS[$p->skill_category] ?? $p->skill_category,
                    $p->status, $p->email_verified_at ? 'Yes' : 'No', $p->created_at?->format('Y-m-d H:i:s'),
                ];
                fputcsv($handle, array_map(static function ($v) {
                    $v = (string) ($v ?? '');
                    return preg_match('/^[\s\x00-\x1F]*[=+@-]/u', $v) ? "'" . $v : $v;
                }, $cells));
            }
            fclose($handle);
        }, 'makeme-participants-' . now()->format('Ymd-His') . '.csv', [
            'Content-Type'=>'text/csv; charset=UTF-8', 'Cache-Control'=>'private, no-store',
            'X-Content-Type-Options'=>'nosniff',
        ]);

        MakeMeActivityLogger::record(
            'participants',
            'exported',
            'Participant data exported',
            null,
            [
                'format' => 'xlsx',
            ]
        );
    }
}
