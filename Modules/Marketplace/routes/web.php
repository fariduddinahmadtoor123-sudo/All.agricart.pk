<?php

use Illuminate\Support\Facades\Route;
use Modules\Marketplace\Http\Controllers\MarketplaceController;

Route::get('/marketplace', MarketplaceController::class)->name('marketplace');
