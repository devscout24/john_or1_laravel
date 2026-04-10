<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CoinStoreController;
use App\Http\Controllers\API\DiscoverController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\PolicyController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Middleware\JWTMiddleware;
use Illuminate\Support\Facades\Route;

// FTP Working

Route::controller(AuthController::class)->group(function () {
    Route::post('/user-social-signin', 'socialSignin');
    Route::post('/user-guest-signin', 'guestSignin');
    Route::post('/user-logout', 'logout');

    // Store FCM Token
    Route::post('/store-user-fcm-token', 'storeFcmToken');
    Route::post('/delete-user-fcm-token', 'deleteFcmToken');;
});

Route::middleware(JWTMiddleware::class)->controller(AuthController::class)->group(function () {
    Route::post('/user-delete', 'deleteUser');
});

Route::controller(ProfileController::class)->middleware(JWTMiddleware::class)->group(function () {
    Route::get('/user-profile', 'profile');
    Route::post('/update-user-profile', 'updateProfile');
    Route::post('/change-user-password', 'changePassword');
});

Route::middleware(JWTMiddleware::class)->controller(NotificationController::class)->group(function () {
    Route::get('/notifications', 'notification');

    // Mark all read / unread
    Route::post('/notifications/mark-all-read', 'markAllRead');
    Route::post('/notifications/mark-all-unread', 'markAllUnread');

    // Delete all
    Route::post('/notifications/delete-all', 'deleteAll');

    // Single operations
    Route::post('/notifications/delete', 'deleteNotification');
    Route::post('/notifications/mark-read', 'markNotificationRead');
    Route::post('/notifications/mark-unread', 'markNotificationUnread');
});

Route::controller(PolicyController::class)->middleware(JWTMiddleware::class)->group(function () {
    Route::get('/get-policies-beach', 'getBeachPolicy');
    Route::get('/get-policies-disclaimers', 'getDisclaimersPolicy');
});


// Production routes

Route::controller(DiscoverController::class)->middleware(JWTMiddleware::class)->group(function () {
    Route::get('/discover', 'index');
    Route::get('/discover/{contentId}', 'show');
    Route::post('/discover/{contentId}/unlock-with-coins', 'unlockWithCoins');
    Route::post('/discover/{contentId}/unlock-with-ad', 'unlockWithAd');
    Route::post('/episodes/{episodeId}/watch-progress', 'updateEpisodeProgress');
});

Route::controller(CoinStoreController::class)->middleware(JWTMiddleware::class)->group(function () {
    Route::get('/coin-store', 'index');
});
