<?php

use Illuminate\Support\Facades\Route;
use Modules\Forex\Http\Controllers\CalendarController;
use Modules\Forex\Http\Controllers\ClientAccountsController;
use Modules\Forex\Http\Controllers\ForexTradingController;
use Modules\Forex\Http\Controllers\LedgerController;
use Modules\Forex\Http\Controllers\MarketWatchController;
use Modules\Forex\Http\Controllers\Mt5AccountsController;
use Modules\Forex\Http\Controllers\PaymentsController;
use Modules\Forex\Http\Controllers\WatchController;

Route::get('/forex', ForexTradingController::class)->name('forex');
Route::post('/forex/market-watch/open', [MarketWatchController::class, 'open'])->name('forex.positions.open');
Route::post('/forex/market-watch/close', [MarketWatchController::class, 'close'])->name('forex.positions.close');
Route::post('/forex/market-watch/pending', [MarketWatchController::class, 'pending'])->name('forex.orders.pending');
Route::post('/forex/market-watch/orders/{position}/levels', [MarketWatchController::class, 'levels'])->name('forex.orders.levels');
Route::post('/forex/market-watch/orders/{position}/cancel', [MarketWatchController::class, 'cancel'])->name('forex.orders.cancel');
Route::post('/forex/market-watch/orders/{position}/close', [MarketWatchController::class, 'closeOrder'])->name('forex.orders.close');
Route::post('/forex/market-watch/watchlist', [MarketWatchController::class, 'watchlist'])->name('forex.market-watch.watchlist');
Route::get('/forex/market-watch/quotes', [MarketWatchController::class, 'quotes'])->name('forex.market-watch.quotes');
Route::get('/forex/market-watch/ledger', [MarketWatchController::class, 'ledger'])->name('forex.market-watch.ledger');
Route::get('/forex/market-watch/payments', [MarketWatchController::class, 'payments'])->name('forex.market-watch.payments');
Route::post('/forex/market-watch/payments', [MarketWatchController::class, 'storePayment'])->name('forex.market-watch.payments.store');
Route::get('/forex/market-watch', MarketWatchController::class)->name('forex.market-watch');
Route::get('/forex/watch', WatchController::class)->name('forex.watch');
Route::get('/forex/calendar', CalendarController::class)->name('forex.calendar');

Route::middleware('super')->group(function (): void {
    Route::get('/forex/mt5-accounts/create', [Mt5AccountsController::class, 'create'])->name('forex.mt5-accounts.create');
    Route::post('/forex/mt5-accounts', [Mt5AccountsController::class, 'store'])->name('forex.mt5-accounts.store');
    Route::get('/forex/mt5-accounts/{account}/connector', [Mt5AccountsController::class, 'connector'])->name('forex.mt5-accounts.connector');
    Route::post('/forex/mt5-accounts/{account}/connector/prepare', [Mt5AccountsController::class, 'prepareConnector'])->name('forex.mt5-accounts.connector.prepare');
    Route::post('/forex/mt5-accounts/{account}/connector/start', [Mt5AccountsController::class, 'startConnector'])->name('forex.mt5-accounts.connector.start');
    Route::post('/forex/mt5-accounts/{account}/rates', [Mt5AccountsController::class, 'rates'])->name('forex.mt5-accounts.rates');
    Route::post('/forex/mt5-accounts/{account}/deactivate', [Mt5AccountsController::class, 'deactivate'])->name('forex.mt5-accounts.deactivate');
    Route::post('/forex/mt5-accounts/{account}/reactivate', [Mt5AccountsController::class, 'reactivate'])->name('forex.mt5-accounts.reactivate');
    Route::get('/forex/mt5-accounts', Mt5AccountsController::class)->name('forex.mt5-accounts');

    Route::get('/forex/client-accounts/create', [ClientAccountsController::class, 'create'])->name('forex.client-accounts.create');
    Route::post('/forex/client-accounts', [ClientAccountsController::class, 'store'])->name('forex.client-accounts.store');
    Route::post('/forex/client-accounts/{account}/payment', [ClientAccountsController::class, 'payment'])->name('forex.client-accounts.payment');
    Route::post('/forex/client-accounts/{account}/disable', [ClientAccountsController::class, 'disable'])->name('forex.client-accounts.disable');
    Route::post('/forex/client-accounts/{account}/enable', [ClientAccountsController::class, 'enable'])->name('forex.client-accounts.enable');
    Route::post('/forex/client-accounts/{account}/archive', [ClientAccountsController::class, 'archive'])->name('forex.client-accounts.archive');
    Route::post('/forex/client-accounts/{account}/restore', [ClientAccountsController::class, 'restore'])->name('forex.client-accounts.restore');
    Route::patch('/forex/client-accounts/{account}', [ClientAccountsController::class, 'update'])->name('forex.client-accounts.update');
    Route::get('/forex/client-accounts', ClientAccountsController::class)->name('forex.client-accounts');

    Route::get('/forex/payments/create', [PaymentsController::class, 'create'])->name('forex.payments.create');
    Route::post('/forex/payments', [PaymentsController::class, 'store'])->name('forex.payments.store');
    Route::post('/forex/payments/{payment}/approve', [PaymentsController::class, 'approve'])->name('forex.payments.approve');
    Route::post('/forex/payments/{payment}/reject', [PaymentsController::class, 'reject'])->name('forex.payments.reject');
    Route::get('/forex/payments', PaymentsController::class)->name('forex.payments');

    Route::get('/forex/ledger/create', [LedgerController::class, 'create'])->name('forex.ledger.create');
    Route::post('/forex/ledger/{account}/wallet', [LedgerController::class, 'depositWallet'])->name('forex.ledger.wallet');
    Route::get('/forex/ledger/{account}', [LedgerController::class, 'show'])->name('forex.ledger.show');
    Route::post('/forex/ledger', [LedgerController::class, 'store'])->name('forex.ledger.store');
    Route::get('/forex/ledger', LedgerController::class)->name('forex.ledger');
});
