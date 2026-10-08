<?php

/**
 * AddCostSheetStatusIdToBriefsTable
 * -----------------------------------------
 * Stores each brief's cost status. New briefs start as Pending and move to Submitted
 * when a plan cost sheet is saved.
 *
 * @package Database\Migrations
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-05
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->ensureStatuses();

        if (!Schema::hasColumn('briefs', 'cost_sheet_status_id')) {
            Schema::table('briefs', function (Blueprint $table) {
                $table->foreignId('cost_sheet_status_id')
                    ->nullable()
                    ->after('brief_status_id')
                    ->constrained('cost_sheet_statuses')
                    ->nullOnDelete();
            });
        }

        $pendingId = DB::table('cost_sheet_statuses')->where('slug', 'pending')->value('id');
        $submittedId = DB::table('cost_sheet_statuses')->where('slug', 'submitted')->value('id');

        if ($submittedId && Schema::hasTable('finance_records')) {
            DB::table('briefs')
                ->whereIn('id', function ($query) {
                    $query->select('brief_id')
                        ->from('finance_records')
                        ->whereNotNull('cost_sheet')
                        ->where('cost_sheet', '!=', '')
                        ->whereNull('deleted_at');
                })
                ->update(['cost_sheet_status_id' => $submittedId]);
        }

        if ($pendingId) {
            DB::table('briefs')
                ->whereNull('cost_sheet_status_id')
                ->update(['cost_sheet_status_id' => $pendingId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('briefs', 'cost_sheet_status_id')) {
            return;
        }

        Schema::table('briefs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cost_sheet_status_id');
        });
    }

    /**
     * Pending and Submitted must exist before the foreign key is filled.
     */
    private function ensureStatuses(): void
    {
        $now = now();

        foreach (['Pending' => 'pending', 'Submitted' => 'submitted'] as $name => $slug) {
            $exists = DB::table('cost_sheet_statuses')->where('slug', $slug)->exists();

            if ($exists) {
                continue;
            }

            DB::table('cost_sheet_statuses')->insert([
                'uuid' => (string) Str::uuid(),
                'name' => $name,
                'slug' => $slug,
                'status' => '1',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
