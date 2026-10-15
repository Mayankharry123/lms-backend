<?php

/**
 * CreateSalesPurchaseOrdersTables
 * -----------------------------------------
 * Migration to create sales_purchase_orders and sales_purchase_order_items tables.
 * Includes brief_id, planner_id, assign_to, created_by, finance_status_id,
 * order details (line items), and PO file upload.
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
        if (!Schema::hasTable('sales_purchase_orders')) {
            Schema::create('sales_purchase_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('po_number')->unique();
                $table->date('po_date')->nullable();

                // Foreign relations
                $table->foreignId('brief_id')
                    ->nullable()
                    ->constrained('briefs')
                    ->nullOnDelete();

                $table->foreignId('planner_id')
                    ->nullable()
                    ->constrained('planners')
                    ->nullOnDelete();

                $table->foreignId('brand_id')
                    ->nullable()
                    ->constrained('brands')
                    ->nullOnDelete();

                $table->foreignId('assign_to')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId('finance_status_id')
                    ->nullable()
                    ->constrained('finance_statuses')
                    ->nullOnDelete();

                // Client / Entity details
                $table->string('company_name')->nullable();
                $table->string('client_name')->nullable();
                $table->string('primary_email')->nullable();
                $table->string('gst_number')->nullable();
                $table->string('pan_number')->nullable();
                $table->string('account_holder_name')->nullable();
                $table->string('account_number')->nullable();
                $table->string('ifsc_code')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('address', 500)->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('country')->nullable();
                $table->string('pincode', 20)->nullable();
                $table->string('campaign')->nullable();
                $table->string('period')->nullable();

                // Financial calculations (subtotal, taxes, total)
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('sgst_rate', 5, 2)->nullable()->default(0);
                $table->decimal('sgst_amount', 15, 2)->nullable()->default(0);
                $table->decimal('cgst_rate', 5, 2)->nullable()->default(0);
                $table->decimal('cgst_amount', 15, 2)->nullable()->default(0);
                $table->decimal('igst_rate', 5, 2)->nullable()->default(0);
                $table->decimal('igst_amount', 15, 2)->nullable()->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->string('amount_in_words', 500)->nullable();

                // Upload PO file
                $table->string('po_file', 500)->nullable()->comment('Uploaded PO file path or URL');

                $table->enum('status', ['1', '2', '15'])
                    ->default('1')
                    ->comment('1 = active, 2 = deactive, 15 = soft delete');

                $table->timestamps();
                $table->softDeletes();

                $table->index('brief_id');
                $table->index('planner_id');
                $table->index('brand_id');
                $table->index('assign_to');
                $table->index('created_by');
                $table->index('finance_status_id');
                $table->index('status');
            });
        }

        if (!Schema::hasTable('sales_purchase_order_items')) {
            Schema::create('sales_purchase_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sales_purchase_order_id')
                    ->constrained('sales_purchase_orders')
                    ->cascadeOnDelete();

                $table->string('order_name')->nullable();
                $table->text('description')->nullable();
                $table->string('hsn_sac')->nullable();
                $table->string('city')->nullable();
                $table->decimal('slot', 12, 2)->default(0);
                $table->decimal('qty', 12, 2)->default(0);
                $table->decimal('rate', 15, 2)->default(0);
                $table->decimal('amount', 15, 2)->default(0);

                $table->enum('status', ['1', '2', '15'])
                    ->default('1')
                    ->comment('1 = active, 2 = deactive, 15 = soft delete');

                $table->timestamps();
                $table->softDeletes();

                $table->index('sales_purchase_order_id');
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_purchase_order_items');
        Schema::dropIfExists('sales_purchase_orders');
    }
};
