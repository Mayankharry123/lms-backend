<?php

/**
 * AddPdfPathToPurchaseOrdersTable
 * -----------------------------------------
 * Adds pdf_path column to purchase_orders table to store relative file path.
 *
 * @package Database\Migrations
 * @author Achal Sharma
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
        if (!Schema::hasColumn('purchase_orders', 'pdf_path')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->string('pdf_path', 500)->nullable()->after('amount_in_words');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('purchase_orders', 'pdf_path')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropColumn('pdf_path');
            });
        }
    }
};
