<?php

/**
 * LaratrustDummySeeder
 * 
 * @author Achal Sharma
 * @package Database\Seeders
 * @version 1.0.1
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds default roles and assigns admin role to the first user.
 * Permissions are seeded by PermissionSeeder (run after this).
 */
class LaratrustDummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('roles')->truncate();
        DB::table('role_user')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $now = now();

        $roles = [
            ['name' => 'admin', 'display_name' => 'Administrator', 'description' => 'Full access to all resources'],
            ['name' => 'manager', 'display_name' => 'Manager', 'description' => 'Manage users and team data'],
            ['name' => 'user', 'display_name' => 'User', 'description' => 'Sales and day-to-day LMS access'],
        ];

        $roleIdByName = [];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']],
                [
                    'uuid' => DB::raw("COALESCE(uuid, '" . Str::uuid() . "')"),
                    'slug' => Str::slug($role['name']),
                    'display_name' => $role['display_name'],
                    'description' => $role['description'],
                    'status' => '1',
                    'updated_at' => $now,
                    'created_at' => DB::raw("COALESCE(created_at, '$now')"),
                ]
            );
            $roleIdByName[$role['name']] = DB::table('roles')->where('name', $role['name'])->value('id');
        }

        $firstUserId = DB::table('users')->orderBy('id')->value('id');
        if ($firstUserId && isset($roleIdByName['admin'])) {
            DB::table('role_user')->updateOrInsert(
                [
                    'role_id' => $roleIdByName['admin'],
                    'user_id' => $firstUserId,
                    'user_type' => 'App\\Models\\User',
                ],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }
}
