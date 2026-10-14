<?php

/**
 * UserSeeder
 * 
 * @author Achal Sharma
 * @package Database\Seeders
 * @version 1.0.1
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $now = now();

        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'status' => '1',
                'email_verified_at' => $now,
            ],
            [
                'name' => 'Manager User',
                'email' => 'manager@example.com',
                'password' => Hash::make('password'),
                'status' => '1',
                'email_verified_at' => $now,
            ],
            [
                'name' => 'Basic User',
                'email' => 'user@example.com',
                'password' => Hash::make('password'),
                'status' => '1',
                'email_verified_at' => $now,
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $user['name'],
                    'password' => $user['password'],
                    'status' => $user['status'],
                    'email_verified_at' => $user['email_verified_at'],
                    'updated_at' => $now,
                    'created_at' => DB::raw("COALESCE(created_at, '$now')"),
                ]
            );
        }
    }
}