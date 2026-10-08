<?php
/**
 * CreateVoucherItemsTable
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
        if (Schema::hasTable('voucher_items')) {
            return;
        }

        Schema::create('voucher_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->index();
            $table->foreignId('voucher_id')
                ->constrained('vouchers')
                ->cascadeOnDelete();

            $table->date('date')->nullable();
            $table->text('particular');
            $table->text('purpose')->nullable();
            $table->string('mode', 100)->nullable();
            $table->decimal('amount', 15, 2)->default(0);

            $table->enum('status', ['1', '2', '15'])
                ->default('1')
                ->comment('1 = active, 2 = inactive, 15 = soft delete');

            $table->timestamps();
            $table->softDeletes();

            $table->index('voucher_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_items');
    }
};
