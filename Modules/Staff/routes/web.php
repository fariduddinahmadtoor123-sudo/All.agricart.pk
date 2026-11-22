<?php

use Illuminate\Support\Facades\Route;
use Modules\Staff\Http\Controllers\StaffController;

Route::get('/staff', StaffController::class)->name('staff');
