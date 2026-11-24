<?php

use Illuminate\Support\Facades\Route;
use Modules\PersonalVault\Http\Controllers\PersonalVaultController;

Route::get('/personal-vault', [PersonalVaultController::class, 'show'])->name('personal-vault');
Route::post('/personal-vault/to-wallet', [PersonalVaultController::class, 'toWallet'])->name('personal-vault.to-wallet');
Route::post('/personal-vault/to-account', [PersonalVaultController::class, 'toAccount'])->name('personal-vault.to-account');
Route::post('/personal-vault/withdraw', [PersonalVaultController::class, 'withdraw'])->name('personal-vault.withdraw');
