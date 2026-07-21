<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('permission_user', 'granted')) {
            Schema::table('permission_user', function (Blueprint $table) {
                $table->boolean('granted')->default(true)->after('permission_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('permission_user', 'granted')) {
            Schema::table('permission_user', function (Blueprint $table) {
                $table->dropColumn('granted');
            });
        }
    }
};