<?php

use Illuminate\Support\Facades\Route;
use Modules\Contacts\Http\Controllers\ContactsController;

Route::get('/contacts', ContactsController::class)->name('contacts');
