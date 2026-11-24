<?php

use App\Http\Controllers\AccountabilityController;
use App\Http\Controllers\AnnualHistoryController;
use App\Http\Controllers\AuditHistoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SpreadsheetController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

// Home Pública e Logo
Route::get('/', [HomeController::class, 'index']);
Route::get('app-logo', [SettingsController::class, 'appLogo']);

// Autenticação
Route::get('login', [AuthController::class, 'login'])->name('login');
Route::post('login', [AuthController::class, 'attemptLogin']);
Route::get('logout', [AuthController::class, 'logout']);

// Recuperação de Senha em Etapas (Código de 6 dígitos via SMTP configurado)
Route::get('forgot-password', [AuthController::class, 'showForgotPassword']);
Route::post('forgot-password', [AuthController::class, 'sendResetCode']);
Route::get('reset-password', [AuthController::class, 'showResetPassword']);
Route::post('reset-password/verify', [AuthController::class, 'verifyResetCode']);
Route::post('reset-password/resend', [AuthController::class, 'resendResetCode']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);

// Rotas Protegidas por Sessão
Route::middleware(['auth.session'])->group(function () {
    Route::get('accountability', [AccountabilityController::class, 'index']);
    Route::get('accountability/create', [AccountabilityController::class, 'create']);
    Route::post('accountability', [AccountabilityController::class, 'store']);
    Route::get('accountability/{id}/edit', [AccountabilityController::class, 'edit'])->whereNumber('id');
    Route::post('accountability/{id}/update', [AccountabilityController::class, 'update'])->whereNumber('id');
    Route::post('accountability/{id}/toggle-pause', [AccountabilityController::class, 'togglePause'])->whereNumber('id');
    Route::get('accountability/{id}', [AccountabilityController::class, 'show'])->whereNumber('id');
    Route::get('accountability/{id}/print-pdf', [AccountabilityController::class, 'printPdf'])->whereNumber('id');
    Route::get('accountability/{id}/report', [AccountabilityController::class, 'report'])->whereNumber('id');
    Route::post('accountability/{id}/assistant', [AccountabilityController::class, 'localAssistant'])->whereNumber('id');
    Route::post('accountability/{id}/analyze-all', [AccountabilityController::class, 'analyzeAll'])->whereNumber('id');
    Route::post('accountability/{id}/submit', [AccountabilityController::class, 'submitAccountability'])->whereNumber('id');
    Route::post('accountability/{id}/send-email', [AccountabilityController::class, 'sendEmail'])->whereNumber('id');
    Route::post('accountability/{id}/delete', [AccountabilityController::class, 'delete'])->whereNumber('id');
    Route::post('accountability/{id}/upload/{itemNumber}', [AccountabilityController::class, 'upload'])->whereNumber('id')->whereNumber('itemNumber');
    Route::post('accountability/{id}/item/{itemNumber}/submit', [AccountabilityController::class, 'submitItem'])->whereNumber('id')->whereNumber('itemNumber');
    Route::post('accountability/{id}/item/{itemNumber}/status', [AccountabilityController::class, 'updateItemStatus'])->whereNumber('id')->whereNumber('itemNumber');
    Route::get('accountability/attachment/{attachmentId}/preview', [AccountabilityController::class, 'previewAttachment'])->whereNumber('attachmentId');
    Route::get('accountability/attachment/{attachmentId}/download', [AccountabilityController::class, 'downloadAttachment'])->whereNumber('attachmentId');
    Route::post('accountability/attachment/{attachmentId}/delete', [AccountabilityController::class, 'deleteAttachment'])->whereNumber('attachmentId');
    Route::post('accountability/attachment/{attachmentId}/analyze', [AccountabilityController::class, 'analyzeAttachment'])->whereNumber('attachmentId');

    // Planilha de Lançamentos da Prestação de Contas
    Route::get('planilha', [SpreadsheetController::class, 'index']);
    Route::post('planilha/save', [SpreadsheetController::class, 'save']);
    Route::post('planilha/extract-ai', [SpreadsheetController::class, 'extractFromAttachments']);

    // Histórico Anual (índice de anos + subpáginas automáticas por ano)
    Route::get('historico-anual', [AnnualHistoryController::class, 'index']);
    Route::get('historico-anual/{year}', [AnnualHistoryController::class, 'showYear'])->whereNumber('year');

    // Histórico de Auditoria
    Route::get('history', [AuditHistoryController::class, 'index']);

    // Usuários e Permissões
    Route::get('users', [UsersController::class, 'index']);
    Route::get('users/create', [UsersController::class, 'create']);
    Route::post('users', [UsersController::class, 'store']);
    Route::get('users/{id}/edit', [UsersController::class, 'edit'])->whereNumber('id');
    Route::post('users/{id}/update', [UsersController::class, 'update'])->whereNumber('id');
    Route::post('users/{id}/delete', [UsersController::class, 'delete'])->whereNumber('id');
    Route::post('users/{id}/role', [UsersController::class, 'updateRole'])->whereNumber('id');

    // Configurações
    Route::get('settings', [SettingsController::class, 'index']);
    Route::post('settings/smtp', [SettingsController::class, 'saveSmtp']);
    Route::post('settings/smtp/toggle', [SettingsController::class, 'toggleSmtpStatus']);
    Route::post('settings/whatsapp', [SettingsController::class, 'saveWhatsapp']);
    Route::post('settings/whatsapp/toggle', [SettingsController::class, 'toggleWhatsappStatus']);
    Route::post('settings/whatsapp/session/start', [SettingsController::class, 'startWhatsappSession']);
    Route::get('settings/whatsapp/session/qr', [SettingsController::class, 'whatsappQr']);
    Route::post('settings/whatsapp/test', [SettingsController::class, 'testWhatsapp']);
    Route::post('settings/whatsapp/notify-overdue', [SettingsController::class, 'notifyOverdueWhatsapp']);
    Route::post('settings/ollama', [SettingsController::class, 'saveOllama']);
    Route::post('settings/general', [SettingsController::class, 'saveGeneral']);
    Route::post('settings/general/remove-logo', [SettingsController::class, 'removeLogo']);
    Route::post('settings/pdf-cover', [SettingsController::class, 'savePdfCover']);
    Route::post('settings/pdf-cover/remove-art', [SettingsController::class, 'removePdfCoverArt']);
    Route::get('settings/pdf-cover/art', [SettingsController::class, 'pdfCoverArt']);

    // Perfil do Usuário
    Route::get('profile', [ProfileController::class, 'index']);
    Route::post('profile/update', [ProfileController::class, 'update']);
    Route::post('profile/avatar/remove', [ProfileController::class, 'removeAvatar']);
    Route::get('profile/avatar/{id}', [ProfileController::class, 'avatar'])->whereNumber('id');
});
