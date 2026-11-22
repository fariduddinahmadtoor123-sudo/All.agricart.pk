<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\FinanceController;

Route::get('/finance', FinanceController::class)->name('finance');
