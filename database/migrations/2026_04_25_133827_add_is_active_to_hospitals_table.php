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
    Schema::table('hospitals', function (Blueprint $table) {
        // أضف الأعمدة الناقصة فقط إذا مش موجودة
        if (!Schema::hasColumn('hospitals', 'type')) {
            $table->string('type', 100)->nullable()->default('عام');
        }
        if (!Schema::hasColumn('hospitals', 'is_active')) {
            $table->boolean('is_active')->default(true);
        }
    });
}

public function down(): void
{
    Schema::table('hospitals', function (Blueprint $table) {
        $table->dropColumn(['type', 'is_active']);
    });
}
};
