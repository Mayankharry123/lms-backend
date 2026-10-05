<?php

/**
 * CreatePurchaseOrderItemsTable
 * -----------------------------------------
 * Stores the line items for a purchase order: description, HSN/SAC, city, quantity, rate, and amount.
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
        if (Schema::hasTable('purchase_order_items')) {
            return;
        }

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')
                ->constrained('purchase_orders')
                ->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->string('hsn_sac')->nullable();
            $table->string('city')->nullable();
            $table->decimal('qty', 12, 2)->default(0);
            $table->decimal('rate', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
