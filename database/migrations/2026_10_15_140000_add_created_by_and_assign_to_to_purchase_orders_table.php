<?php

/**
 * AddCreatedByAndAssignToToPurchaseOrdersTable
 * -----------------------------------------
 * Adds created_by and assign_to columns to purchase_orders table.
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
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('pdf_path');
                $table->index('created_by');
            }

            if (!Schema::hasColumn('purchase_orders', 'assign_to')) {
                $table->unsignedBigInteger('assign_to')->nullable()->after('created_by');
                $table->index('assign_to');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_orders', 'assign_to')) {
                $table->dropIndex(['assign_to']);
                $table->dropColumn('assign_to');
            }

            if (Schema::hasColumn('purchase_orders', 'created_by')) {
                $table->dropIndex(['created_by']);
                $table->dropColumn('created_by');
            }
        });
    }
};
