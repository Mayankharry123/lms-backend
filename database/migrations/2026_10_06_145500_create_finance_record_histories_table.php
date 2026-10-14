<?php

/**
 * CreateFinanceRecordHistoriesTable
 * -----------------------------------------
 * This migration creates the finance_record_histories table,
 * which tracks status, cost sheet updates, and assignment history for finance records.
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
        Schema::create('finance_record_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('finance_record_id')
                ->constrained('finance_records')
                ->onDelete('cascade');

            $table->foreignId('brief_id')
                ->nullable()
                ->constrained('briefs')
                ->onDelete('cascade');

            $table->foreignId('planner_id')
                ->nullable()
                ->constrained('planners')
                ->onDelete('cascade');

            $table->foreignId('finance_status_id')
                ->nullable()
                ->constrained('finance_statuses')
                ->onDelete('restrict');

            $table->string('cost_sheet')->nullable()->comment('Uploaded cost sheet file path or URL snapshot');

            $table->unsignedBigInteger('assign_by')->nullable();
            $table->unsignedBigInteger('assign_to')->nullable();

            $table->text('comment')->nullable();

            $table->enum('status', ['1', '2', '15'])
                ->default('1')
                ->comment('1 = active, 2 = deactive, 15 = soft delete');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('assign_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('assign_to')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index('finance_record_id');
            $table->index('brief_id');
            $table->index('planner_id');
            $table->index('finance_status_id');
            $table->index('assign_by');
            $table->index('assign_to');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_record_histories');
    }
};
