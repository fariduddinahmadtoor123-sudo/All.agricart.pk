<?php

use Illuminate\Support\Facades\Route;
use Modules\Approvals\Http\Controllers\ApprovalsController;

Route::get('/approvals', ApprovalsController::class)->name('approvals');
