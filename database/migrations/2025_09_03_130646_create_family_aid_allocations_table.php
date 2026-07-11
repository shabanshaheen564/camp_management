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
        Schema::create('family_aid_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('aid_distribution_id'); // Reference to the distribution
            $table->unsignedBigInteger('guardian_id'); // Which family/guardian
            $table->integer('family_size'); // Number of family members at time of allocation
            $table->decimal('individual_share', 8, 2); // Share per individual
            $table->decimal('family_share', 8, 2); // Total family share
            $table->decimal('allocated_quantity', 8, 2); // Actual allocated amount
            $table->enum('receipt_status', ['pending', 'distributed', 'collected', 'missed'])->default('pending');
            $table->enum('priority_level', ['normal', 'vulnerable', 'urgent'])->default('normal');
            $table->text('special_needs')->nullable(); // "Family has infants needing milk", "Sick family needs more water"
            $table->timestamp('distributed_at')->nullable(); // When it was actually distributed
            $table->unsignedBigInteger('distributed_by')->nullable(); // User who distributed
            $table->text('distribution_notes')->nullable(); // Notes during distribution
            $table->timestamps();

            $table->foreign('aid_distribution_id')->references('id')->on('aid_distributions')->onDelete('cascade');
            $table->foreign('guardian_id')->references('id')->on('guardians')->onDelete('cascade');
            $table->foreign('distributed_by')->references('id')->on('users')->onDelete('set null');
            
            $table->unique(['aid_distribution_id', 'guardian_id']); // One allocation per family per distribution
            $table->index(['receipt_status', 'priority_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_aid_allocations');
    }
};
