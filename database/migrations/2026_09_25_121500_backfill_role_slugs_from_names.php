<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Fill roles.slug from name where it was never generated.
     */
    public function up(): void
    {
        $roles = DB::table('roles')
            ->where(function ($query) {
                $query->whereNull('slug')->orWhere('slug', '');
            })
            ->orderBy('id')
            ->get();

        foreach ($roles as $role) {
            $baseSlug = Str::slug((string) $role->name);
            if ($baseSlug === '') {
                $baseSlug = 'role';
            }

            $slug = $baseSlug;
            $counter = 1;

            while (
                DB::table('roles')
                    ->where('slug', $slug)
                    ->where('id', '!=', $role->id)
                    ->exists()
            ) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            DB::table('roles')->where('id', $role->id)->update([
                'slug' => $slug,
                'updated_at' => $role->updated_at,
            ]);
        }
    }

    /**
     * Existing slug values are kept. This backfill is not reversed.
     */
    public function down(): void
    {
        //
    }
};
