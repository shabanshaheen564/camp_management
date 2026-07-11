<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampController;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\FamilyMemberController;

// تسجيل الدخول (بدون Authentication)
Route::post('/login', [AuthController::class, 'login']);

// كل الطلبات الأخرى تحتاج توكن
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // المخيم الخاص باليوزر
    Route::get('/camps/{camp}', [CampController::class, 'show']);
    Route::get('/camps/{camp}/guardians', [GuardianController::class, 'byCamp']);
    Route::get('/camps/{camp}/statistics', [CampController::class, 'statistics']);

    // العائلات (أرباب الأسر)
    Route::post('/guardians', [GuardianController::class, 'store']);
    Route::put('/guardians/{guardian}', [GuardianController::class, 'update']);
    Route::delete('/guardians/{guardian}', [GuardianController::class, 'destroy']);
    Route::get('/guardians/{guardian}/members', [FamilyMemberController::class, 'byGuardian']);

    // أفراد العائلة
    Route::post('/family-members', [FamilyMemberController::class, 'store']);
    Route::delete('/family-members/{member}', [FamilyMemberController::class, 'destroy']);
});