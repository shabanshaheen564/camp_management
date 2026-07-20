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
        Schema::table('family_members', function (Blueprint $table) {
            $table->string('marital_status', 20)->default('single')->after('is_disabled');
        });
    }

    public function down(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            $table->dropColumn('marital_status');
        });
    }
};
