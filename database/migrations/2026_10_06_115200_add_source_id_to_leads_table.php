<?php

/**
 * AddSourceIdToLeadsTable
 * -----------------------------------------
 * Adds source_id column to leads table referencing lead_source table.
 *
 * @package Database\Migrations
 * @author   Achal Sharma
 * @version 1.0.0
 * @since 2026-10-06
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('leads', 'source_id')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->foreignId('source_id')
                    ->nullable()
                    ->after('department_id')
                    ->constrained('lead_source')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('leads', 'source_id')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropConstrainedForeignId('source_id');
            });
        }
    }
};
