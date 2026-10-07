<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentModeTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modes = [
            'UPI',
            'Bank',
            'Cash',
        ];

        $now = Carbon::now();

        foreach ($modes as $name) {
            $slug = Str::slug($name);
            $existing = DB::table('payment_mode_types')->where('slug', $slug)->first();

            if ($existing) {
                DB::table('payment_mode_types')->where('id', $existing->id)->update([
                    'name' => $name,
                    'status' => '1',
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('payment_mode_types')->insert([
                    'uuid' => (string) Str::uuid(),
                    'name' => $name,
                    'slug' => $slug,
                    'status' => '1',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
