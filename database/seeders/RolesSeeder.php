<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'projects.create', 'projects.view', 'projects.update', 'projects.delete', 'projects.archive',
            'tasks.create', 'tasks.view', 'tasks.update', 'tasks.delete', 'tasks.assign',
            'users.invite', 'users.manage', 'users.view',
            'imputations.create', 'imputations.view', 'imputations.manage',
            'sprints.create', 'sprints.manage',
            'milestones.create', 'milestones.manage',
            'reports.view',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->syncPermissions(Permission::all());

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions([
            'projects.create', 'projects.view', 'projects.update', 'projects.delete', 'projects.archive',
            'tasks.create', 'tasks.view', 'tasks.update', 'tasks.delete', 'tasks.assign',
            'users.invite', 'users.manage', 'users.view',
            'imputations.view', 'imputations.manage',
            'sprints.create', 'sprints.manage',
            'milestones.create', 'milestones.manage',
            'reports.view',
            'settings.manage',
        ]);

        $projectManager = Role::firstOrCreate(['name' => 'project_manager']);
        $projectManager->syncPermissions([
            'projects.create', 'projects.view', 'projects.update', 'projects.archive',
            'tasks.create', 'tasks.view', 'tasks.update', 'tasks.delete', 'tasks.assign',
            'users.invite', 'users.view',
            'imputations.create', 'imputations.view',
            'sprints.create', 'sprints.manage',
            'milestones.create', 'milestones.manage',
            'reports.view',
        ]);

        $developer = Role::firstOrCreate(['name' => 'developer']);
        $developer->syncPermissions([
            'projects.view',
            'tasks.create', 'tasks.view', 'tasks.update',
            'users.view',
            'imputations.create', 'imputations.view',
            'reports.view',
        ]);

        $observer = Role::firstOrCreate(['name' => 'observer']);
        $observer->syncPermissions([
            'projects.view',
            'tasks.view',
            'users.view',
            'imputations.view',
            'reports.view',
        ]);

        $client = Role::firstOrCreate(['name' => 'client']);
        $client->syncPermissions([
            'projects.view',
            'milestones.manage',
            'reports.view',
        ]);

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@sprojects.test'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $adminUser->assignRole('super_admin');
    }
}
