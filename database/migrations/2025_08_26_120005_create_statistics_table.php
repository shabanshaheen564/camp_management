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
        Schema::create('statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camp_id')->constrained('camps')->onDelete('cascade');
            $table->integer('family_numbers')->unsigned()->default(0);
            $table->decimal('family_member_avg', 5, 2)->default(0.00);
            $table->integer('disabled_people_number')->unsigned()->default(0);
            $table->integer('female_number')->unsigned()->default(0);
            $table->integer('male_number')->unsigned()->default(0);
            $table->integer('married_number')->unsigned()->default(0);
            $table->integer('single_number')->unsigned()->default(0);
            $table->integer('old_people_number')->unsigned()->default(0);
            $table->decimal('capacity_ratio', 5, 2)->default(0.00);
            $table->timestamps();
            
            // Index for performance
            $table->index(['camp_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statistics');
    }
};