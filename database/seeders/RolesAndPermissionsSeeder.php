<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $modules = config('makeme_permissions.modules', []);
        $protectedModules = config(
            'makeme_permissions.protected_modules',
            []
        );

        foreach ($modules as $module => $details) {
            foreach ($details['actions'] as $action => $label) {
                Permission::findOrCreate(
                    "{$module}.{$action}",
                    'web'
                );
            }
        }

        foreach ($protectedModules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::findOrCreate(
                    "{$module}.{$action}",
                    'web'
                );
            }
        }

        $superAdmin = Role::findOrCreate(
            'Super Admin',
            'web'
        );

        Role::findOrCreate('Administrator', 'web');
        Role::findOrCreate('Manager', 'web');

        $viewer = Role::findOrCreate('Viewer', 'web');

        // Super Admin gets every registered permission.
        $superAdmin->syncPermissions(
            Permission::where('guard_name', 'web')->get()
        );

        // Viewer receives read-only MakeMe permissions.
        // Existing Viewer assignments outside MakeMe are preserved.
        $viewPermissions = [];

        foreach ($modules as $module => $details) {
            if (array_key_exists('view', $details['actions'])) {
                $viewPermissions[] = "{$module}.view";
            }
        }

        $viewer->givePermissionTo($viewPermissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
