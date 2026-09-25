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
        Schema::create('brand_imports', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('stored_path')->nullable();
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('processed_records')->default(0);
            $table->unsignedInteger('created_records')->default(0);
            $table->unsignedInteger('updated_records')->default(0);
            $table->unsignedInteger('failed_records')->default(0);
            $table->unsignedInteger('current_chunk')->default(0);
            $table->text('error_message')->nullable();
            $table->json('failed_details')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                ->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_imports');
    }
};
