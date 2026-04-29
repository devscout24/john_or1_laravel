<?php

use App\Http\Controllers\Web\Backend\AdminUserController;
use App\Http\Controllers\Web\Backend\AppUserManagementController;
use App\Http\Controllers\Web\Backend\DashboardController;
use App\Http\Controllers\Web\Backend\DynamicPageController;
use App\Http\Controllers\Web\Backend\EpisodeManagementController;
use App\Http\Controllers\Web\Backend\EpisodeStatsController;
use App\Http\Controllers\Web\Backend\LockController;
use App\Http\Controllers\Web\Backend\SeriesManagementController;
use App\Http\Controllers\Web\Backend\SystemController;
use App\Http\Controllers\Web\Backend\UserManagementController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;


Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    return redirect()->back()->with('success', 'Cache cleared successfully.');
})->name('cache.clear');

// Lock Screen
Route::controller(LockController::class)->group(function () {
    Route::get('/screen/lock', 'showLockScreen')->name('screen.lock.show');
    Route::post('/screen/lock', 'lock')->name('screen.lock');
    Route::post('/screen/unlock', 'unlock')->name('screen.lock.unlock');
});


// Dashboard
Route::controller(DashboardController::class)->group(function () {
    Route::get('/dashboard', 'index')->name('dashboard');
});

Route::controller(SystemController::class)->group(function () {
    Route::get('/system-settings', 'systemSettings')->name('system.settings');
    Route::post('/system-settings-update', 'systemSettingsUpdate')->name('system.settings.update');

    Route::get('/credential-settings/{type}', 'credentialSettings')->name('system.settings.credential');
    Route::post('/credential-settings-update', 'credentialSettingsUpdate')->name('system.settings.credential.update');
});

Route::controller(DynamicPageController::class)->group(function () {
    Route::get('/dynamic-pages', 'index')->name('dynamic.pages');
    Route::get('/dynamic-pages/{page}', 'show')->name('dynamic.pages.show');
    Route::get('/dynamic-pages/{page}/edit', 'edit')->name('dynamic.pages.edit');
    Route::put('/dynamic-pages/{page}', 'update')->name('dynamic.pages.update');
    Route::post('/dynamic-pages/{page}/status', 'updateStatus')->name('dynamic.pages.status');
});


Route::controller(AdminUserController::class)->group(function () {
    Route::get('/profile', 'profile')->name('admin.user.profile');
    Route::get('/edit-profile', 'editProfile')->name('admin.user.profile.edit');
    Route::post('/profile/update', 'updateProfile')->name('admin.user.profile.update');
    Route::get('/email-change/{token}', 'showEmailChangeForm')->name('email.change.verify');
    Route::post('/email-change/confirm', 'confirmEmailChange')->name('email.change.confirm');
});


Route::controller(UserManagementController::class)->group(function () {
    Route::get('admin/users/data', 'data')->name('admin.user.data');

    Route::get('/user-lists', 'index')->name('admin.user.lists');
    Route::get('/user-lists/{user}', 'show')->name('admin.user.show');

    Route::get('/user-lists/{user}/edit', 'edit')->name('admin.user.edit');
    Route::post('/user-lists/{user}', 'update')->name('admin.user.update');

    Route::post('/user-lists/{user}/status', 'updateUserStatus')->name('admin.user.status.update');
    Route::post('/user-lists/{user}/role', 'updateUserRole')->name('admin.user.role.update');

    Route::get('/create-user', 'create')->name('admin.user.create');
    Route::post('/admin/user/store', 'store')->name('admin.user.store');
});

Route::controller(AppUserManagementController::class)->group(function () {
    Route::get('/app-users', 'index')->name('app-user.index');
    Route::get('/app-users/data', 'data')->name('app-user.data');
    Route::get('/app-users/{appUser}', 'show')->name('app-user.show');
    Route::post('/app-users/{appUser}/status', 'updateUserStatus')->name('app-user.status.update');
});

Route::controller(SeriesManagementController::class)->group(function () {
    Route::get('/series-management', 'index')->name('series.index');
    Route::get('/series-management/create', 'create')->name('series.create');
    Route::post('/series-management', 'store')->name('series.store');
    Route::get('/series-management/{content}/edit', 'edit')->name('series.edit');
    Route::put('/series-management/{content}', 'update')->name('series.update');
    Route::post('/series-management/{content}/status', 'toggleStatus')->name('series.status.toggle');
    Route::post('/series-management/{content}/delete', 'destroy')->name('series.destroy');
});

Route::controller(EpisodeManagementController::class)->group(function () {
    Route::get('/series-management/{content}/episodes/create', 'create')->name('episodes.create');
    Route::post('/series-management/{content}/episodes', 'store')->name('episodes.store');
    Route::get('/series-management/{content}/episodes/{episode}/edit', 'edit')->name('episodes.edit');
    Route::put('/series-management/{content}/episodes/{episode}', 'update')->name('episodes.update');
    Route::post('/series-management/{content}/episodes/{episode}/status', 'toggleStatus')->name('episodes.status.toggle');
    Route::post('/series-management/{content}/episodes/{episode}/delete', 'destroy')->name('episodes.destroy');
});

Route::get('/episode-stats', [EpisodeStatsController::class, 'index'])->name('episode.stats');
Route::get('/episode-stats-data', [EpisodeStatsController::class, 'data'])->name('episode.stats.data');
Route::get('/episode-stats/{episode}/gifts', [EpisodeStatsController::class, 'giftsHistory'])->name('episode.stats.gifts');
