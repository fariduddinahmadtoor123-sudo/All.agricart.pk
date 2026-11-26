<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Http\Controllers\ColorsController;
use Modules\Settings\Http\Controllers\PrintingController;
use Modules\Settings\Http\Controllers\RolesController;
use Modules\Settings\Http\Controllers\SettingsController;

Route::middleware('super')->group(function (): void {
    Route::get('/settings', SettingsController::class)->name('settings');
    Route::get('/roles/create', [RolesController::class, 'create'])->name('roles.create');
    Route::post('/roles', [RolesController::class, 'store'])->name('roles.store');
    Route::get('/roles', RolesController::class)->name('roles');
    Route::get('/colors/create', [ColorsController::class, 'create'])->name('colors.create');
    Route::get('/colors/{colorScheme}/edit', [ColorsController::class, 'edit'])->name('colors.edit');
    Route::post('/colors', [ColorsController::class, 'store'])->name('colors.store');
    Route::put('/colors/{colorScheme}', [ColorsController::class, 'update'])->name('colors.update');
    Route::delete('/colors/{colorScheme}', [ColorsController::class, 'destroy'])->name('colors.destroy');
    Route::get('/colors', ColorsController::class)->name('colors');
    Route::get('/settings/printing', PrintingController::class)->name('settings.printing');
    Route::post('/settings/printing', [PrintingController::class, 'store'])->name('settings.printing.store');
    Route::put('/settings/printing/{printProfile}', [PrintingController::class, 'update'])->name('settings.printing.update');
    Route::delete('/settings/printing/{printProfile}', [PrintingController::class, 'destroy'])->name('settings.printing.destroy');
    Route::post('/settings/printing/{printProfile}/default', [PrintingController::class, 'setDefault'])->name('settings.printing.default');
    Route::get('/settings/printing/{printProfile}/preview', [PrintingController::class, 'preview'])->name('settings.printing.preview');
    Route::get('/settings/printing/{printProfile}/barcode', [PrintingController::class, 'barcodePreview'])->name('settings.printing.barcode');
});
