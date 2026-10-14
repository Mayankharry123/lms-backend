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
            if (!Schema::hasColumn('purchase_order_items', 'status')) {
                Schema::table('purchase_order_items', function (Blueprint $table) {
                    $table->enum('status', ['1', '2', '15'])
                        ->default('1')
                        ->comment('1 = active, 2 = deactive, 15 = soft delete')
                        ->after('amount');
                    $table->index('status');
                });
            }

            if (!Schema::hasColumn('purchase_order_items', 'deleted_at')) {
                Schema::table('purchase_order_items', function (Blueprint $table) {
                    $table->softDeletes();
                });
            }

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
            $table->enum('status', ['1', '2', '15'])
                ->default('1')
                ->comment('1 = active, 2 = deactive, 15 = soft delete');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
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
