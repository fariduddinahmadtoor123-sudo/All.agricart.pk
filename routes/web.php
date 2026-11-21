<?php

use App\Http\Controllers\ColorsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ForexTradingController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\NotesController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/forex', ForexTradingController::class)->name('forex');
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
    Route::get('/notes/create', [NotesController::class, 'create'])->name('notes.create');
    Route::get('/notes/{note}/edit', [NotesController::class, 'edit'])->name('notes.edit');
    Route::post('/notes', [NotesController::class, 'store'])->name('notes.store');
    Route::put('/notes/{note}', [NotesController::class, 'update'])->name('notes.update');
    Route::delete('/notes/{note}', [NotesController::class, 'destroy'])->name('notes.destroy');
    Route::get('/notes', NotesController::class)->name('notes');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
