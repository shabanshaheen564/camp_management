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
        Schema::create('aid_distributions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('camp_id'); // Which camp receives the aid
            $table->unsignedBigInteger('aid_type_id'); // Type of aid
            $table->decimal('available_quantity', 10, 2); // Total quantity available
            $table->decimal('distributed_quantity', 10, 2)->default(0); // How much has been distributed
            $table->string('time_period')->default('weekly'); // weekly, monthly, daily
            $table->integer('target_beneficiaries')->default(0); // Number of individuals or families targeted
            $table->string('distribution_basis')->default('individual'); // individual, family, household
            $table->date('distribution_date'); // When the distribution was made/planned
            $table->date('expiry_date')->nullable(); // When the aid expires (for food/medicine)
            $table->enum('status', ['pending', 'active', 'completed', 'expired'])->default('active');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->text('special_notes')->nullable(); // Special requirements or notes
            $table->unsignedBigInteger('created_by'); // User who created this distribution
            $table->unsignedBigInteger('managed_by')->nullable(); // User managing the distribution
            $table->timestamps();

            $table->foreign('camp_id')->references('id')->on('camps')->onDelete('cascade');
            $table->foreign('aid_type_id')->references('id')->on('aid_types')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('managed_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['camp_id', 'aid_type_id', 'distribution_date']);
            $table->index(['status', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aid_distributions');
    }
};