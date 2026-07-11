<?php
// =============================================
// FILE 1: app/Models/Hospital.php
// =============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hospital extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'phone',
        'type',
        'is_active',
    ];

    protected $casts = [
        'latitude'  => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}


// =============================================
// FILE 2: database/migrations/xxxx_create_hospitals_table.php
// =============================================
// Run: php artisan make:migration create_hospitals_table
// Then replace the content with this:
/*
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospitals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('type', 100)->nullable()->default('عام');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospitals');
    }
};
*/


// =============================================
// FILE 3: routes/web.php  — أضف هذه الـ routes
// =============================================
/*
use App\Http\Controllers\MapController;

Route::prefix('map')->name('map.')->middleware(['auth'])->group(function () {
    Route::get('/',                    [MapController::class, 'index'])->name('index');
    Route::get('/camps-data',          [MapController::class, 'campsData'])->name('camps.data');
    Route::get('/hospitals-data',      [MapController::class, 'hospitalsData'])->name('hospitals.data');
    Route::post('/hospitals',          [MapController::class, 'storeHospital'])->name('hospitals.store');
    Route::delete('/hospitals/{id}',   [MapController::class, 'destroyHospital'])->name('hospitals.destroy');
});
*/