<?php

/**
 * AddBriefIdAndPlannerIdToProformaInvoicesTable
 * 
 * @package Database\Migrations
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-15
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
        Schema::table('proforma_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('proforma_invoices', 'brief_id')) {
                $table->unsignedBigInteger('brief_id')->nullable()->after('brand_id');
                $table->index('brief_id');
            }
            if (!Schema::hasColumn('proforma_invoices', 'planner_id')) {
                $table->unsignedBigInteger('planner_id')->nullable()->after('brief_id');
                $table->index('planner_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proforma_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('proforma_invoices', 'planner_id')) {
                $table->dropIndex(['planner_id']);
                $table->dropColumn('planner_id');
            }
            if (Schema::hasColumn('proforma_invoices', 'brief_id')) {
                $table->dropIndex(['brief_id']);
                $table->dropColumn('brief_id');
            }
        });
    }
};
