<?php

use Illuminate\Support\Facades\Route;
use Modules\PersonalVault\Http\Controllers\PersonalVaultController;

Route::get('/personal-vault', PersonalVaultController::class)->name('personal-vault');
