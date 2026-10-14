<?php
/**
 * UpdateNotificationsCategoryEnum
 *
 * @package Database\Migrations
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-08
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `notifications` MODIFY COLUMN `category` VARCHAR(50) NULL DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `notifications` MODIFY COLUMN `category` ENUM('lead', 'brief', 'planner', 'pre-lead') NULL DEFAULT NULL");
    }
};
