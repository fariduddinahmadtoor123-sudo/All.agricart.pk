<?php

use Illuminate\Support\Facades\Route;
use Modules\ExternalApps\Http\Controllers\ExternalAppsController;

Route::get('/external-apps', ExternalAppsController::class)->name('external-apps');
