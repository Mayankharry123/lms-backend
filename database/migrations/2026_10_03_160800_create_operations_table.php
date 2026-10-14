<?php

/**
 * CreateOperationsTable
 * -----------------------------------------
 * This migration creates the operations table,
 * which stores operations linked to a brief, planner, and operation status.
 *
 * @package Database\Migrations
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-03
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
        Schema::create('operations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('brief_id')
                ->constrained('briefs')
                ->onDelete('cascade');

            $table->foreignId('planner_id')
                ->constrained('planners')
                ->onDelete('cascade');

            $table->foreignId('operation_status_id')
                ->constrained('operation_statuses')
                ->onDelete('restrict');

            $table->unsignedBigInteger('assign_by')->nullable();
            $table->unsignedBigInteger('assign_to')->nullable();

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

            $table->index('brief_id');
            $table->index('planner_id');
            $table->index('operation_status_id');
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
        Schema::dropIfExists('operations');
    }
};
