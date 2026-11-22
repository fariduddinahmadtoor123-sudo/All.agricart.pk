<?php

use Illuminate\Support\Facades\Route;
use Modules\Purchasing\Http\Controllers\PurchasingController;

Route::get('/purchasing', PurchasingController::class)->name('purchasing');
