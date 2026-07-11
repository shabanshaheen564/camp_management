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
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camp_id')->constrained('camps')->onDelete('cascade');
            $table->string('first_name')->index();
            $table->string('second_name');
            $table->string('third_name');
            $table->string('family_name')->index();
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female'])->index();
            $table->string('card_id')->unique()->index();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->index();
            $table->string('nationality');
            $table->integer('family_member_number')->unsigned()->default(0);
            $table->boolean('is_disabled')->default(false)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};