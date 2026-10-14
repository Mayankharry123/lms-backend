<?php

/**
 * AddFinanceRecordIdToPurchaseOrdersTable
 * -----------------------------------------
 * Links each purchase order to the finance record it was raised from.
 *
 * @package Database\Migrations
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-05
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
        if (Schema::hasColumn('purchase_orders', 'finance_record_id')) {
            return;
        }

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('finance_record_id')
                ->after('publisher_id')
                ->constrained('finance_records')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('purchase_orders', 'finance_record_id')) {
            return;
        }

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('finance_record_id');
        });
    }
};
