<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('sgst_rate', 5, 2)->nullable()->after('subtotal');
            $table->decimal('sgst_amount', 15, 2)->nullable()->after('sgst_rate');
            $table->decimal('cgst_rate', 5, 2)->nullable()->after('sgst_amount');
            $table->decimal('cgst_amount', 15, 2)->nullable()->after('cgst_rate');
            $table->decimal('igst_rate', 5, 2)->nullable()->after('cgst_amount');
            $table->decimal('igst_amount', 15, 2)->nullable()->after('igst_rate');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'sgst_rate',
                'sgst_amount',
                'cgst_rate',
                'cgst_amount',
                'igst_rate',
                'igst_amount',
            ]);
        });
    }
};
