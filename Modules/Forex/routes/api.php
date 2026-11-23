<?php

use Illuminate\Support\Facades\Route;
use Modules\Forex\Http\Controllers\ConnectorSyncController;

Route::post('/forex/connector/sync', ConnectorSyncController::class)->name('forex.connector.sync');
Route::post('/forex/connector/commands', [ConnectorSyncController::class, 'commands'])->name('forex.connector.commands');
Route::post('/forex/connector/executions', [ConnectorSyncController::class, 'executions'])->name('forex.connector.executions');
