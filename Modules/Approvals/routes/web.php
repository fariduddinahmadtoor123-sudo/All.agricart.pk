<?php

use Illuminate\Support\Facades\Route;
use Modules\Approvals\Http\Controllers\ApprovalsController;

Route::middleware('super')->group(function (): void {
    Route::get('/approvals', ApprovalsController::class)->name('approvals');
    Route::post('/approvals/{registration}/approve', [ApprovalsController::class, 'approve'])->name('approvals.approve');
    Route::post('/approvals/{registration}/reject', [ApprovalsController::class, 'reject'])->name('approvals.reject');
    Route::get('/approvals/{registration}/file/{kind}', [ApprovalsController::class, 'file'])->name('approvals.file');
});
