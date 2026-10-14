<?php

/**
 * AddAddressToPurchaseOrdersTable
 * -----------------------------------------
 * Stores the publisher address selected for a purchase order.
 * The address is copied from DGPlay and is not a local lookup table.
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
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'publisher_address_id')) {
                $table->unsignedBigInteger('publisher_address_id')->nullable()->after('publisher_id');
            }

            if (!Schema::hasColumn('purchase_orders', 'address')) {
                $table->string('address', 500)->nullable()->after('bank_name');
            }

            if (!Schema::hasColumn('purchase_orders', 'city')) {
                $table->string('city')->nullable()->after('address');
            }

            if (!Schema::hasColumn('purchase_orders', 'state')) {
                $table->string('state')->nullable()->after('city');
            }

            if (!Schema::hasColumn('purchase_orders', 'country')) {
                $table->string('country')->nullable()->after('state');
            }

            if (!Schema::hasColumn('purchase_orders', 'pincode')) {
                $table->string('pincode', 20)->nullable()->after('country');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $columns = ['publisher_address_id', 'address', 'city', 'state', 'country', 'pincode'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('purchase_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
