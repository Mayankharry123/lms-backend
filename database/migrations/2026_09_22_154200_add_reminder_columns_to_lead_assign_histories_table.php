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
        if (!Schema::hasTable('lead_assign_histories')) {
            return;
        }

        Schema::table('lead_assign_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('lead_assign_histories', 'reminder')) {
                $table->boolean('reminder')->default(0)->after('lead_comment');
            }
            if (!Schema::hasColumn('lead_assign_histories', 'reminder_at')) {
                $table->dateTime('reminder_at')->nullable()->after('reminder');
            }
            if (!Schema::hasColumn('lead_assign_histories', 'reminder_before')) {
                $table->unsignedInteger('reminder_before')->nullable()->after('reminder_at');
            }
            if (!Schema::hasColumn('lead_assign_histories', 'reminder_before_unit')) {
                $table->string('reminder_before_unit', 20)->nullable()->after('reminder_before');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('lead_assign_histories')) {
            return;
        }

        Schema::table('lead_assign_histories', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('lead_assign_histories', 'reminder') ? 'reminder' : null,
                Schema::hasColumn('lead_assign_histories', 'reminder_at') ? 'reminder_at' : null,
                Schema::hasColumn('lead_assign_histories', 'reminder_before') ? 'reminder_before' : null,
                Schema::hasColumn('lead_assign_histories', 'reminder_before_unit') ? 'reminder_before_unit' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
