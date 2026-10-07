<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('voucher_items', function (Blueprint $table) {
            if (!Schema::hasColumn('voucher_items', 'payment_mode_type_id')) {
                $table->foreignId('payment_mode_type_id')
                    ->nullable()
                    ->after('mode')
                    ->constrained('payment_mode_types')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voucher_items', function (Blueprint $table) {
            if (Schema::hasColumn('voucher_items', 'payment_mode_type_id')) {
                $table->dropForeign(['payment_mode_type_id']);
                $table->dropColumn('payment_mode_type_id');
            }
        });
    }
};
