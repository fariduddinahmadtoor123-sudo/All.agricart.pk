<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Http\Controllers\ColorsController;
use Modules\Settings\Http\Controllers\RolesController;
use Modules\Settings\Http\Controllers\SettingsController;

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
