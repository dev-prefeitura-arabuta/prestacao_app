<?php

use App\Http\Controllers\Api\MobileApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function () {
    Route::get('ping', [MobileApiController::class, 'ping']);
    Route::get('quick-users', [MobileApiController::class, 'quickLoginUsers']);
    Route::post('login', [MobileApiController::class, 'login']);
    Route::post('forgot-password', [MobileApiController::class, 'forgotPassword']);
    Route::post('reset-password/verify', [MobileApiController::class, 'verifyResetCode']);
    Route::post('reset-password', [MobileApiController::class, 'resetPassword']);
    Route::post('logout', [MobileApiController::class, 'logout']);
    Route::get('me', [MobileApiController::class, 'me']);
    Route::post('profile', [MobileApiController::class, 'updateProfile']);
    Route::delete('profile/avatar', [MobileApiController::class, 'removeAvatar']);
    Route::get('avatar/{id}', [MobileApiController::class, 'avatar'])->whereNumber('id');
    Route::get('accountabilities', [MobileApiController::class, 'accountabilities']);
    Route::post('accountabilities', [MobileApiController::class, 'createAccountability']);
    Route::get('accountabilities/{id}', [MobileApiController::class, 'showAccountability'])->whereNumber('id');
    Route::post('accountabilities/{id}/upload/{itemNumber}', [MobileApiController::class, 'uploadAttachment'])
        ->whereNumber('id')
        ->whereNumber('itemNumber');
    Route::post('accountabilities/{id}/submit', [MobileApiController::class, 'submitAccountability'])
        ->whereNumber('id');
    Route::post('accountabilities/{id}/remind-whatsapp', [MobileApiController::class, 'remindWhatsapp'])
        ->whereNumber('id');
    Route::get('attachments/{attachmentId}/preview', [MobileApiController::class, 'previewAttachment'])
        ->whereNumber('attachmentId');
    Route::delete('attachments/{attachmentId}', [MobileApiController::class, 'deleteAttachment'])
        ->whereNumber('attachmentId');
    Route::get('annual-history', [MobileApiController::class, 'annualHistory']);
    Route::get('spreadsheet', [MobileApiController::class, 'spreadsheetSummary']);
    Route::get('audit-history', [MobileApiController::class, 'auditHistory']);
    Route::get('users', [MobileApiController::class, 'usersList']);
    Route::get('users/{id}', [MobileApiController::class, 'showUser'])->whereNumber('id');
    Route::post('users/{id}', [MobileApiController::class, 'updateUser'])->whereNumber('id');
    Route::get('settings-summary', [MobileApiController::class, 'settingsSummary']);
});
