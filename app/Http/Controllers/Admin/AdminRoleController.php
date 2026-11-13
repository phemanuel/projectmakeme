<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AdminRoleController extends Controller
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
            403,
            'You are not authorized to manage roles.'
        );
    }

    private function protectRole(Role $role): void
    {
        abort_if(
            $role->guard_name !== 'web',
            404
        );

        abort_if(
            $role->name === 'Super Admin',
            403,
            'The Super Admin role is protected.'
        );
    }

    private function modules(): array
    {
        return config('makeme_permissions.modules', []);
    }

    private function allowedPermissions(): array
    {
        $names = [];

        foreach ($this->modules() as $module => $details) {
            foreach (array_keys($details['actions']) as $action) {
                $names[] = "{$module}.{$action}";
            }
        }

        return $names;
    }

    private function validationRules(?Role $role = null): array
    {
        $unique = Rule::unique('roles', 'name')
            ->where('guard_name', 'web');

        if ($role) {
            $unique->ignore($role->id);
        }

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                $unique,
                Rule::notIn(['Super Admin']),
            ],
            'permissions' => [
                'sometimes',
                'array',
            ],
            'permissions.*' => [
                'string',
                Rule::in($this->allowedPermissions()),
            ],
        ];
    }

    private function syncEditablePermissions(
        Role $role,
        array $selected
    ): void {
        $allowed = $this->allowedPermissions();

        // Preserve permissions outside this editor.
        $existingOtherPermissions = $role->permissions
            ->pluck('name')
            ->reject(function ($name) use ($allowed) {
                return in_array($name, $allowed, true);
            })
            ->values()
            ->all();

        // Protected permissions may never be assigned
        // to non-Super-Admin roles through this editor.
        $protected = [];

        foreach (
            config('makeme_permissions.protected_modules', [])
            as $module => $actions
        ) {
            foreach ($actions as $action) {
                $protected[] = "{$module}.{$action}";
            }
        }

        $existingOtherPermissions = array_values(
            array_diff($existingOtherPermissions, $protected)
        );

        $final = array_values(array_unique(array_merge(
            $existingOtherPermissions,
            $selected
        )));

        $permissionModels = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $final)
            ->get();

        $role->syncPermissions($permissionModels);
    }

    public function index()
    {
        $this->authorizeManager();

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->with('permissions')
            ->withCount('users')
            ->orderByRaw(
                "CASE WHEN name = 'Super Admin' THEN 0 ELSE 1 END"
            )
            ->orderBy('name')
            ->get();

        $modules = $this->modules();

        return view(
            'admin.roles.index',
            compact('roles', 'modules')
        );
    }

    public function store(Request $request)
    {
        $this->authorizeManager();

        $validated = $request->validate(
            $this->validationRules()
        );

        $roleName = trim($validated['name']);

        DB::transaction(function () use ($roleName, $validated) {
            $role = Role::create([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $this->syncEditablePermissions(
                $role,
                $validated['permissions'] ?? []
            );
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function update(Request $request, Role $role)
    {
        $this->authorizeManager();
        $this->protectRole($role);

        $validated = $request->validate(
            $this->validationRules($role)
        );

        $roleName = trim($validated['name']);

        DB::transaction(function () use ($role, $roleName, $validated) {
            $role->name = $roleName;
            $role->save();

            $role->load('permissions');

            $this->syncEditablePermissions(
                $role,
                $validated['permissions'] ?? []
            );
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $this->authorizeManager();
        $this->protectRole($role);

        // Only unused custom roles can be deleted.
        abort_if(
            in_array(
                $role->name,
                ['Administrator', 'Manager', 'Viewer'],
                true
            ),
            403,
            'Default roles cannot be deleted.'
        );

        abort_if(
            $role->users()->exists(),
            422,
            'This role is assigned to users. Reassign them first.'
        );

        $role->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
