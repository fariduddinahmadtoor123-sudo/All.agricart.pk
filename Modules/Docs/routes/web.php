<?php

use Illuminate\Support\Facades\Route;
use Modules\Docs\Http\Controllers\NotesController;

Route::middleware('super')->group(function (): void {
    Route::get('/notes/create', [NotesController::class, 'create'])->name('notes.create');
    Route::get('/notes/{note}/edit', [NotesController::class, 'edit'])->name('notes.edit');
    Route::post('/notes', [NotesController::class, 'store'])->name('notes.store');
    Route::put('/notes/{note}', [NotesController::class, 'update'])->name('notes.update');
    Route::delete('/notes/{note}', [NotesController::class, 'destroy'])->name('notes.destroy');
    Route::get('/notes', NotesController::class)->name('notes');
});
