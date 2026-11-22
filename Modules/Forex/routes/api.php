<?php

use Illuminate\Support\Facades\Route;
use Modules\Forex\Http\Controllers\ConnectorSyncController;

Route::post('/forex/connector/sync', ConnectorSyncController::class)->name('forex.connector.sync');
