<?php

use Csouza\NotificationManagement\Http\Controllers\NotificationPreferenceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('api/notification-preferences')->group(function () {
    Route::get('/', [NotificationPreferenceController::class, 'index'])->name('notification-preferences.index');
    Route::put('/', [NotificationPreferenceController::class, 'update'])->name('notification-preferences.update');
    Route::post('/enable', [NotificationPreferenceController::class, 'enable'])->name('notification-preferences.enable');
    Route::post('/disable', [NotificationPreferenceController::class, 'disable'])->name('notification-preferences.disable');
    Route::get('/channels', [NotificationPreferenceController::class, 'channels'])->name('notification-preferences.channels');
    Route::get('/types', [NotificationPreferenceController::class, 'types'])->name('notification-preferences.types');
    Route::get('/history', [NotificationPreferenceController::class, 'history'])->name('notification-preferences.history');
});
