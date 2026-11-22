<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\CatalogController;

Route::get('/catalog', CatalogController::class)->name('catalog');
