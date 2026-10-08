<?php

/**
 * AddAddressAndGstToBrandsTable
 * 
 * @package Database\Migrations
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-08
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
        Schema::table('brands', function (Blueprint $table) {
            if (!Schema::hasColumn('brands', 'address')) {
                $table->text('address')->nullable()->after('website');
            }
            if (!Schema::hasColumn('brands', 'gst_no')) {
                $table->string('gst_no', 50)->nullable()->after('address');
            }
            if (!Schema::hasColumn('brands', 'gst_numbers')) {
                $table->json('gst_numbers')->nullable()->after('gst_no');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if (Schema::hasColumn('brands', 'gst_numbers')) {
                $table->dropColumn('gst_numbers');
            }
            if (Schema::hasColumn('brands', 'gst_no')) {
                $table->dropColumn('gst_no');
            }
            if (Schema::hasColumn('brands', 'address')) {
                $table->dropColumn('address');
            }
        });
    }
};
