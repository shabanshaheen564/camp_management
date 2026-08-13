<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampController;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\FamilyMemberController;
use App\Http\Controllers\NotificationController;

// تسجيل الدخول (بدون Authentication)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

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

    // استيراد/تصدير إكسل من التطبيق (جديد) - خطوتين زي الويب بالظبط
    Route::post('/camps/{camp}/guardians/import/preview', [FamilyMemberController::class, 'apiImportPreview']);
    Route::post('/camps/{camp}/guardians/import/execute', [FamilyMemberController::class, 'apiImportExecute']);
    Route::get('/camps/{camp}/guardians/export', [FamilyMemberController::class, 'apiExport']);

    // الإشعارات من التطبيق (جديد) - نفس NotificationController المستخدم بالويب بالضبط
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::patch('/notifications/sections/{section}/read', [NotificationController::class, 'markSectionRead']);
});