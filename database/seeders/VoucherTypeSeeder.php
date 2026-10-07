<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoucherTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $voucherTypes = [
            'Staff Welfare Expenses',
            'Tour & Travelling Expenses',
            'Office Expenses',
            'Site Repair & Maintenance',
            'Refreshment Expenses',
            'Printing & Stationery Expenses',
            'Business Promotion Expenses',
            'Transportation Expenses',
            'Telephone Expenses',
            'Hotel Accommodation Expenses',
        ];

        $now = Carbon::now();

        foreach ($voucherTypes as $name) {
            $slug = Str::slug($name);
            $existing = DB::table('voucher_types')->where('slug', $slug)->first();

            if ($existing) {
                DB::table('voucher_types')->where('id', $existing->id)->update([
                    'name' => $name,
                    'status' => '1',
                    'updated_at' => $now,
                ]);
                continue;
            }

            DB::table('voucher_types')->insert([
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
