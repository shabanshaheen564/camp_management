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
        Schema::create('aid_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Food packages, Drinking water, Medicine, Blankets, etc.
            $table->string('name_en')->nullable(); // English name for system use
            $table->text('description')->nullable(); // Additional details
            $table->string('unit'); // packages, liters, boxes, pieces, etc.
            $table->string('category')->default('basic'); // basic, medical, clothing, food, water
            $table->string('icon')->default('box'); // FontAwesome icon
            $table->string('color')->default('primary'); // Bootstrap color
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aid_types');
    }
};