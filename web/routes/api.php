<?php

use App\Http\Controllers\Api\MobileApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function () {
    // Públicas
    Route::get('ping', [MobileApiController::class, 'ping']);
    Route::get('quick-users', [MobileApiController::class, 'quickLoginUsers']);
    Route::post('login', [MobileApiController::class, 'login'])->middleware('throttle:login');
    Route::post('forgot-password', [MobileApiController::class, 'forgotPassword'])->middleware('throttle:password-reset');
    Route::post('reset-password/verify', [MobileApiController::class, 'verifyResetCode'])->middleware('throttle:password-reset');
    Route::post('reset-password', [MobileApiController::class, 'resetPassword'])->middleware('throttle:password-reset');
    Route::post('logout', [MobileApiController::class, 'logout']);
    Route::get('avatar/{id}', [MobileApiController::class, 'avatar'])->whereNumber('id');

    // Usuário autenticado
    Route::middleware('auth.mobile')->group(function () {
        Route::get('me', [MobileApiController::class, 'me']);
        Route::post('profile', [MobileApiController::class, 'updateProfile']);
        Route::delete('profile/avatar', [MobileApiController::class, 'removeAvatar']);
        Route::get('accountabilities', [MobileApiController::class, 'accountabilities']);
        Route::get('accountabilities/{id}', [MobileApiController::class, 'showAccountability'])->whereNumber('id');
        Route::post('accountabilities/{id}/upload/{itemNumber}', [MobileApiController::class, 'uploadAttachment'])
            ->whereNumber('id')
            ->whereNumber('itemNumber');
        Route::post('accountabilities/{id}/submit', [MobileApiController::class, 'submitAccountability'])
            ->whereNumber('id');
        Route::get('attachments/{attachmentId}/preview', [MobileApiController::class, 'previewAttachment'])
            ->whereNumber('attachmentId')
            ->name('mobile.attachments.preview');
        Route::delete('attachments/{attachmentId}', [MobileApiController::class, 'deleteAttachment'])
            ->whereNumber('attachmentId');
    });

    // Somente administradores
    Route::middleware('auth.mobile:admin')->group(function () {
        Route::post('accountabilities', [MobileApiController::class, 'createAccountability']);
        Route::post('accountabilities/{id}/remind-whatsapp', [MobileApiController::class, 'remindWhatsapp'])
            ->whereNumber('id');
        Route::get('annual-history', [MobileApiController::class, 'annualHistory']);
        Route::get('audit-history', [MobileApiController::class, 'auditHistory']);
        Route::get('users', [MobileApiController::class, 'usersList']);
        Route::get('users/{id}', [MobileApiController::class, 'showUser'])->whereNumber('id');
        Route::post('users/{id}', [MobileApiController::class, 'updateUser'])->whereNumber('id');
        Route::get('settings-summary', [MobileApiController::class, 'settingsSummary']);
        Route::get('notifications', [MobileApiController::class, 'notifications']);
        Route::post('notifications/toggle', [MobileApiController::class, 'toggleNotifications']);
        Route::post('notifications/read-all', [MobileApiController::class, 'markAllNotificationsRead']);
        Route::post('notifications/{id}/read', [MobileApiController::class, 'markNotificationRead'])->whereNumber('id');
    });
});
