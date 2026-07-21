<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CampController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\FamilyMemberController;
use App\Http\Controllers\AidController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\NotificationController;

// صفحة الترحيب
Route::get('/', fn() => view('welcome'))->name('home');

// تسجيل الدخول والخروج
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// المسارات المحمية (تحتاج تسجيل دخول)
Route::middleware('auth')->group(function () {

    // لوحة التحكم
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ==================== المخيمات ====================
    Route::get('/camps', [CampController::class, 'index'])->middleware('permission:camp.view')->name('camps.index');
    Route::post('/camps', [CampController::class, 'store'])->middleware('permission:camp.create')->name('camps.store');
    Route::match(['put', 'patch'], '/camps/{camp}', [CampController::class, 'update'])->middleware('permission:camp.update')->name('camps.update');
    Route::delete('/camps/{camp}', [CampController::class, 'destroy'])->middleware('permission:camp.delete')->name('camps.destroy');
    Route::patch('/camps/{camp}/toggle', [CampController::class, 'toggleStatus'])->middleware('permission:camp.manage')->name('camps.toggle');
    Route::get('/camps/import', [CampController::class, 'showImportForm'])->middleware('permission:camp.manage')->name('camps.import.form');
    Route::post('/camps/import/preview', [CampController::class, 'importPreview'])->middleware('permission:camp.manage')->name('camps.import.preview');
    Route::post('/camps/import', [CampController::class, 'importExecute'])->middleware('permission:camp.manage')->name('camps.import');

    // ==================== العائلات ====================
    Route::get('/families', [FamilyController::class, 'index'])->middleware('permission:guardian.view')->name('families.index');
    Route::post('/families', [FamilyController::class, 'store'])->middleware('permission:guardian.create')->name('families.store');
    Route::match(['put', 'patch'], '/families/{family}', [FamilyController::class, 'update'])->middleware('permission:guardian.update')->name('families.update');
    Route::delete('/families/{family}', [FamilyController::class, 'destroy'])->middleware('permission:guardian.delete')->name('families.destroy');
    Route::get('/families/{guardian}/members-list', [FamilyController::class, 'getMembersList'])->name('families.members-list');
    Route::post('/families/{guardian}/members', [FamilyController::class, 'storeMember'])->middleware('permission:family_member.create')->name('families.store-member');
    Route::delete('/families/members/{member}', [FamilyController::class, 'destroyMember'])->middleware('permission:family_member.delete')->name('families.destroy-member');

    // ==================== استيراد الأفراد ====================
    Route::get('/members/import', [FamilyMemberController::class, 'showImportForm'])->middleware('permission:import.families')->name('members.import.form');
    Route::post('/members/import/preview', [FamilyMemberController::class, 'importPreview'])->middleware('permission:import.families')->name('members.import.preview');
    Route::post('/members/import', [FamilyMemberController::class, 'importExecute'])->middleware('permission:import.families')->name('members.import');

    // ==================== المساعدات ====================
    Route::get('/aid', [AidController::class, 'index'])->middleware('permission:aid.view')->name('aid.index');
    Route::post('/aid', [AidController::class, 'store'])->middleware('permission:aid.create')->name('aid.store');
    Route::match(['put', 'patch'], '/aid/{aid}', [AidController::class, 'update'])->middleware('permission:aid.update')->name('aid.update');
    Route::delete('/aid/{aid}', [AidController::class, 'destroy'])->middleware('permission:aid.delete')->name('aid.destroy');

    // ==================== التقارير ====================
    Route::get('/reports', [ReportController::class, 'index'])->middleware('permission:report.view')->name('reports.index');
    Route::get('/reports/print', [ReportController::class, 'printStatistics'])->middleware('permission:report.export')->name('reports.print');
    Route::get('/reports/export/camps', [ReportController::class, 'exportCamps'])->middleware('permission:report.export')->name('reports.export.camps');
    Route::get('/reports/export/families', [ReportController::class, 'exportFamilies'])->middleware('permission:report.export')->name('reports.export.families');
    Route::get('/reports/export/members', [ReportController::class, 'exportMembers'])->middleware('permission:report.export')->name('reports.export.members');

    // ==================== الخريطة ====================
    Route::get('/map', [MapController::class, 'index'])->middleware('permission:map.view')->name('map.index');
    Route::get('/map/data', [MapController::class, 'data'])->middleware('permission:map.view')->name('map.data');
    Route::prefix('map')->name('map.')->middleware('permission:map.manage')->group(function () {
        Route::get('/camps-data', [MapController::class, 'campsData'])->name('camps.data');
        Route::get('/hospitals-data', [MapController::class, 'hospitalsData'])->name('hospitals.data');
        Route::post('/hospitals', [MapController::class, 'storeHospital'])->name('hospitals.store');
        Route::delete('/hospitals/{id}', [MapController::class, 'destroyHospital'])->name('hospitals.destroy');
    });

    // ==================== المستخدمون (إدارة) ====================
    Route::middleware('admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'create', 'edit']);
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggleStatus'])->name('users.toggle');
        Route::get('/users/{user}/permissions', [UserController::class, 'getPermissions'])->name('users.permissions.show');
        Route::patch('/users/{user}/permissions', [UserController::class, 'updatePermissions'])->name('users.permissions.update');
    });

    // ==================== الأدوار (إدارة) ====================
    Route::middleware('admin')->group(function () {
        Route::resource('roles', RoleController::class)->except(['show', 'create', 'edit']);
        Route::patch('/roles/{role}/toggle', [RoleController::class, 'toggleStatus'])->name('roles.toggle');
        Route::get('/roles/{role}/permissions', [RoleController::class, 'getRolePermissions'])->name('roles.permissions.show');
        Route::patch('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.permissions.update');
    });

    // ==================== الإشعارات ====================
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});
