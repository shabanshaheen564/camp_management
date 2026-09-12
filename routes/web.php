<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CampController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\FamilyDeletionController;
use App\Http\Controllers\FamilyMemberController;
use App\Http\Controllers\AidController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\NotificationController;
use App\Http\Middleware\AuthorizeImportCampRows;

Route::get('/', fn() => view('welcome'))->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/camps', [CampController::class, 'index'])->middleware('permission:camp.view')->name('camps.index');
    Route::post('/camps', [CampController::class, 'store'])->middleware('permission:camp.create')->name('camps.store');
    Route::match(['put', 'patch'], '/camps/{camp}', [CampController::class, 'update'])->middleware('permission:camp.update')->name('camps.update');
    Route::delete('/camps/{camp}', [CampController::class, 'destroy'])->middleware('permission:camp.delete')->name('camps.destroy');
    Route::patch('/camps/{camp}/toggle', [CampController::class, 'toggleStatus'])->middleware('permission:camp.manage')->name('camps.toggle');
    Route::get('/camps/import', [CampController::class, 'showImportForm'])->middleware('permission:camp.manage')->name('camps.import.form');
    Route::post('/camps/import/preview', [CampController::class, 'importPreview'])->middleware('permission:camp.manage')->name('camps.import.preview');
    Route::post('/camps/import', [CampController::class, 'importExecute'])->middleware('permission:camp.manage')->name('camps.import');

    Route::get('/families', [FamilyController::class, 'index'])->middleware('permission:guardian.view')->name('families.index');
    Route::post('/families', [FamilyController::class, 'store'])->middleware('permission:guardian.create')->name('families.store');
    Route::match(['put', 'patch'], '/families/{guardian}', [FamilyController::class, 'update'])->middleware('permission:guardian.update')->name('families.update');
    Route::delete('/families/{guardian}', [FamilyDeletionController::class, 'destroy'])->middleware('permission:guardian.delete')->name('families.destroy');
    Route::get('/families-trash', [FamilyController::class, 'trash'])->middleware('permission:guardian.view-trash')->name('families.trash');
    Route::delete('/families-trash/force-delete-all', [FamilyDeletionController::class, 'forceDeleteAll'])->middleware('permission:guardian.force-delete')->name('families.force-delete-all');
    Route::patch('/families-trash/{id}/restore', [FamilyController::class, 'restore'])->middleware('permission:guardian.restore')->name('families.restore');
    Route::delete('/families-trash/{id}/force-delete', [FamilyDeletionController::class, 'forceDelete'])->middleware('permission:guardian.force-delete')->name('families.force-delete');
    Route::get('/families/{guardian}/members-list', [FamilyController::class, 'getMembersList'])->middleware('permission:family_member.view')->name('families.members-list');
    Route::post('/families/{guardian}/members', [FamilyController::class, 'storeMember'])->middleware('permission:family_member.create')->name('families.store-member');
    Route::delete('/families/members/{member}', [FamilyMemberController::class, 'destroyMember'])->middleware('permission:family_member.delete')->name('families.destroy-member');

    Route::get('/members/import', [FamilyMemberController::class, 'showImportForm'])->middleware('permission:import.families')->name('members.import.form');
    Route::post('/members/import/preview', [FamilyMemberController::class, 'importPreview'])->middleware('permission:import.families')->name('members.import.preview');
    Route::post('/members/import', [FamilyMemberController::class, 'importExecute'])
        ->middleware(['permission:import.families', AuthorizeImportCampRows::class])
        ->name('members.import');

    Route::get('/aid', [AidController::class, 'index'])->middleware('permission:aid.view')->name('aid.index');
    Route::post('/aid', [AidController::class, 'store'])->middleware('permission:aid.create')->name('aid.store');
    Route::match(['put', 'patch'], '/aid/{aid}', [AidController::class, 'update'])->middleware('permission:aid.update')->name('aid.update');
    Route::delete('/aid/{aid}', [AidController::class, 'destroy'])->middleware('permission:aid.delete')->name('aid.destroy');

    Route::get('/reports', [ReportController::class, 'index'])->middleware('permission:report.view')->name('reports.index');
    Route::get('/reports/print', [ReportController::class, 'printStatistics'])->middleware('permission:report.export')->name('reports.print');
    Route::get('/reports/export/camps', [ReportController::class, 'exportCamps'])->middleware('permission:report.export')->name('reports.export.camps');
    Route::get('/reports/export/families', [ReportController::class, 'exportFamilies'])->middleware('permission:report.export')->name('reports.export.families');
    Route::get('/reports/export/members', [ReportController::class, 'exportMembers'])->middleware('permission:report.export')->name('reports.export.members');

    Route::get('/map', [MapController::class, 'index'])->middleware('permission:map.view')->name('map.index');
    Route::get('/map/data', [MapController::class, 'data'])->middleware('permission:map.view')->name('map.data');
    Route::prefix('map')->name('map.')->middleware('permission:map.manage')->group(function () {
        Route::get('/hospitals-data', [MapController::class, 'hospitalsData'])->name('hospitals.data');
        Route::post('/hospitals', [MapController::class, 'storeHospital'])->name('hospitals.store');
        Route::post('/hospitals/import', [MapController::class, 'importHospitals'])->name('hospitals.import');
        Route::delete('/hospitals/{hospital}', [MapController::class, 'destroyHospital'])->name('hospitals.destroy');
    });

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    Route::get('/users', [UserController::class, 'index'])->middleware('permission:user.view')->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:user.create')->name('users.store');
    Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update'])->middleware('permission:user.update')->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:user.delete')->name('users.destroy');
    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->middleware('permission:user.manage')->name('users.toggle-status');
    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->middleware('permission:user.manage')->name('users.toggle');
    Route::get('/users/{user}/activity', [UserController::class, 'activity'])->middleware('permission:user.view')->name('users.activity');
    Route::get('/users/{user}/permissions', [UserController::class, 'getPermissions'])->middleware('permission:user.view')->name('users.permissions');
    Route::patch('/users/{user}/permissions', [UserController::class, 'updatePermissions'])->middleware('permission:user.update')->name('users.permissions.update');

    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:role.view')->name('roles.index');
    Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:role.create')->name('roles.store');
    Route::match(['put', 'patch'], '/roles/{role}', [RoleController::class, 'update'])->middleware('permission:role.update')->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:role.delete')->name('roles.destroy');
    Route::patch('/roles/{role}/toggle', [RoleController::class, 'toggleStatus'])->middleware('permission:role.update')->name('roles.toggle');
    Route::get('/roles/{role}/permissions', [RoleController::class, 'getRolePermissions'])->middleware('permission:role.view')->name('roles.permissions');
    Route::patch('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->middleware('permission:role.update')->name('roles.permissions.update');
});
