<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'permission_id'], 'permission_user_unique');
            $table->index(['user_id'], 'permission_user_user_id_index');
            $table->index(['permission_id'], 'permission_user_permission_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_user');
    }
};
