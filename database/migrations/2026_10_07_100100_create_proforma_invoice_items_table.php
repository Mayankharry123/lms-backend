<?php

/**
 * CreateProformaInvoiceItemsTable
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
        if (Schema::hasTable('proforma_invoice_items')) {
            return;
        }

        Schema::create('proforma_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proforma_invoice_id')
                ->constrained('proforma_invoices')
                ->cascadeOnDelete();

            $table->text('order_name')->nullable();
            $table->string('hsn_sac')->nullable();
            $table->string('city')->nullable();
            $table->decimal('slot', 12, 2)->default(0);
            $table->decimal('rate', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);

            $table->enum('status', ['1', '2', '15'])
                ->default('1')
                ->comment('1 = active, 2 = deactive, 15 = soft delete');

            $table->timestamps();
            $table->softDeletes();

            $table->index('proforma_invoice_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proforma_invoice_items');
    }
};
