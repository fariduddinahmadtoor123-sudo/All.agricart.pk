<?php

use Illuminate\Support\Facades\Route;
use Modules\Logistics\Http\Controllers\LogisticsController;

Route::get('/logistics', LogisticsController::class)->name('logistics');
