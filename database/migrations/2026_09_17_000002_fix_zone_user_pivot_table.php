<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('zone_user', function (Blueprint $table) {
            $table->dropForeign(['organisation_id']);
            $table->dropUnique(['organisation_id', 'user_id']);
            $table->dropIndex(['organisation_id']);
            $table->renameColumn('organisation_id', 'zone_id');
        });

        Schema::table('zone_user', function (Blueprint $table) {
            $table->foreign('zone_id')->references('id')->on('zones')->cascadeOnDelete();
            $table->unique(['zone_id', 'user_id']);
            $table->index('zone_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zone_user', function (Blueprint $table) {
            $table->dropForeign(['zone_id']);
            $table->dropUnique(['zone_id', 'user_id']);
            $table->dropIndex(['zone_id']);
            $table->renameColumn('zone_id', 'organisation_id');
        });

        Schema::table('zone_user', function (Blueprint $table) {
            $table->foreign('organisation_id')->references('id')->on('organisations')->cascadeOnDelete();
            $table->unique(['organisation_id', 'user_id']);
            $table->index('organisation_id');
        });
    }
};