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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // camp_created, guardian_registered, family_member_added, etc.
            $table->string('title'); // Activity title in Arabic
            $table->text('description'); // Activity description
            $table->string('icon')->default('info-circle'); // FontAwesome icon
            $table->string('color')->default('primary'); // Bootstrap color class
            $table->unsignedBigInteger('user_id'); // User who performed the action
            $table->nullableMorphs('subject'); // The entity that was acted upon (camp, guardian, etc.)
            $table->json('properties')->nullable(); // Additional data about the activity
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['created_at', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
