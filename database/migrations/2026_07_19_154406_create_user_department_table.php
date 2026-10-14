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
        if (!Schema::hasTable('user_department')) {
            Schema::create('user_department', function (Blueprint $table) {
                $table->id();
                $table->foreignId('department_id')
                    ->constrained('departments')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->timestamps();
                $table->softDeletes();

                // Prevent duplicate user-department mappings
                $table->unique(['user_id', 'department_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_department');
    }
};