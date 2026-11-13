<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    private function authorizeManager(): void
    {
        $user = Auth::user();

        abort_unless(
            $user &&
            (int) $user->user_type === 1 &&
            (int) $user->user_status === 1 &&
            $user->applicant_type !== 'WAITLIST' &&
            $user->hasRole('Super Admin'),
            403
        );
    }

    private function protectAccount(User $user): void
    {
        abort_unless((int) $user->user_type === 1, 404);

        abort_if(
            (int) $user->id === (int) Auth::id() ||
            $user->hasRole('Super Admin'),
            403,
            'This administrative account is protected.'
        );
    }

    private function assignableRoles()
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->where('name', '!=', 'Super Admin')
            ->orderBy('name')
            ->get();
    }

    private function roleRules(): array
    {
        return [
            'required',
            Rule::exists('roles', 'name')
                ->where('guard_name', 'web'),
            Rule::notIn(['Super Admin']),
        ];
    }

    public function index()
    {
        $this->authorizeManager();

        $users = User::query()
            ->where('user_type', 1)
            ->with('roles')
            ->orderByDesc('id')
            ->paginate(15);

        $roles = $this->assignableRoles();

        return view(
            'admin.users.index',
            compact('users', 'roles')
        );
    }

    public function store(Request $request)
    {
        $this->authorizeManager();

        $request->merge([
            'email' => strtolower(
                trim((string) $request->input('email'))
            ),
        ]);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'surname' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email'),
            ],
            'password' => [
                'required', 'confirmed', Password::min(10),
            ],
            'role' => $this->roleRules(),
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'first_name' => $validated['first_name'],
                'surname' => $validated['surname'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'user_type' => 1,
                'user_status' => 1,
                'email_verified_status' => 1,
                'email_verified_at' => now(),
                'reg_date' => now(),
            ]);

            $user->assignRole($validated['role']);
        });

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Administrator created successfully.');
    }

    public function update(Request $request, User $adminUser)
    {
        $this->authorizeManager();
        $this->protectAccount($adminUser);

        $request->merge([
            'email' => strtolower(
                trim((string) $request->input('email'))
            ),
        ]);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'surname' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')
                    ->ignore($adminUser->id),
            ],
            'password' => [
                'nullable', 'confirmed', Password::min(10),
            ],
            'role' => $this->roleRules(),
        ]);

        DB::transaction(function () use ($adminUser, $validated) {
            $adminUser->first_name = $validated['first_name'];
            $adminUser->surname = $validated['surname'];
            $adminUser->email = $validated['email'];

            if (!empty($validated['password'])) {
                $adminUser->password = Hash::make(
                    $validated['password']
                );
            }

            $adminUser->save();
            $adminUser->syncRoles([$validated['role']]);
        });

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Administrator updated successfully.');
    }

    public function toggleStatus(User $adminUser)
    {
        $this->authorizeManager();
        $this->protectAccount($adminUser);

        $adminUser->user_status =
            (int) $adminUser->user_status === 1 ? 0 : 1;

        $adminUser->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Account status updated successfully.');
    }
}
