<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'super_admin',
            'school_admin',
            'teacher',
            'student',
            'parent',
        ];

        $permissions = [
            'view dashboard',
            'manage users',
            'manage roles',
            'view permissions',
            'manage school setup',
            'manage settings',
            'manage academic years',
            'manage classes',
            'manage subjects',
            'manage teachers',
            'manage students',
            'manage parents',
            'manage lessons',
            'manage assignments',
            'manage quizzes',
            'manage assessments',
            'manage attendance',
            'manage timetables',
            'manage announcements',
            'manage email communications',
            'view reports',
            'publish reports',
            'manage website content',
        ];

        $permissionRecords = collect($permissions)->mapWithKeys(function (string $permission): array {
            $record = Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);

            return [$permission => $record];
        });

        foreach ($roles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            if (in_array($roleName, ['super_admin', 'school_admin'], true)) {
                $role->syncPermissions($permissionRecords->values());
            } elseif ($roleName === 'teacher') {
                $role->syncPermissions($permissionRecords->only(['view dashboard', 'manage lessons', 'manage assignments', 'manage quizzes', 'manage assessments', 'manage attendance', 'manage announcements', 'view reports'])->values());
            } elseif ($roleName === 'student') {
                $role->syncPermissions($permissionRecords->only(['view dashboard', 'view reports'])->values());
            } elseif ($roleName === 'parent') {
                $role->syncPermissions($permissionRecords->only(['view dashboard', 'view reports'])->values());
            }
        }
    }
}
