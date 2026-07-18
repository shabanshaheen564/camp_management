<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CampController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\AidController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;

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

    // المخيمات
    Route::resource('camps', CampController::class)->except(['show', 'create', 'edit']);

    // العائلات
    Route::resource('families', FamilyController::class)->except(['show', 'create', 'edit']);
    Route::get('/families/{guardian}/members-list', [FamilyController::class, 'getMembersList'])->name('families.members-list');
    Route::post('/families/{guardian}/members', [FamilyController::class, 'storeMember'])->name('families.store-member');
    Route::delete('/families/members/{member}', [FamilyController::class, 'destroyMember'])->name('families.destroy-member');

    // المساعدات
    Route::resource('aid', AidController::class)->except(['show', 'create', 'edit']);

    // التقارير
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/camps', [ReportController::class, 'exportCamps'])->name('reports.export.camps');

    // الخريطة
    Route::get('/map', [MapController::class, 'index'])->name('map.index');
    Route::get('/map/data', [MapController::class, 'data'])->name('map.data');

    // المستخدمون
    Route::resource('users', UserController::class)->except(['show', 'create', 'edit']);
    Route::patch('/users/{user}/toggle', [UserController::class, 'toggleStatus'])->name('users.toggle');

    // الأدوار
    Route::resource('roles', RoleController::class)->except(['show', 'create', 'edit']);
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::resource('users', UserController::class);
        Route::patch('users/{user}/toggle', [UserController::class, 'toggleStatus'])->name('users.toggle');
        Route::resource('roles', RoleController::class);
    });
    Route::prefix('map')->name('map.')->middleware(['auth'])->group(function () {
    Route::get('/',                    [MapController::class, 'index'])->name('index');
    Route::get('/camps-data',          [MapController::class, 'campsData'])->name('camps.data');
    Route::get('/hospitals-data',      [MapController::class, 'hospitalsData'])->name('hospitals.data');
    Route::post('/hospitals',          [MapController::class, 'storeHospital'])->name('hospitals.store');
    Route::delete('/hospitals/{id}',   [MapController::class, 'destroyHospital'])->name('hospitals.destroy');
});
});
