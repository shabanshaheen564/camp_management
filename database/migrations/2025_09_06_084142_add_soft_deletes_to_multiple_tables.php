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
        // Add soft deletes to camps table
        Schema::table('camps', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to guardians table
        Schema::table('guardians', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to family_members table
        Schema::table('family_members', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to aid_types table
        Schema::table('aid_types', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to family_aid_allocations table
        Schema::table('family_aid_allocations', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to users table
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove soft deletes from camps table
        Schema::table('camps', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Remove soft deletes from guardians table
        Schema::table('guardians', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Remove soft deletes from family_members table
        Schema::table('family_members', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Remove soft deletes from aid_types table
        Schema::table('aid_types', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Remove soft deletes from family_aid_allocations table
        Schema::table('family_aid_allocations', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Remove soft deletes from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
