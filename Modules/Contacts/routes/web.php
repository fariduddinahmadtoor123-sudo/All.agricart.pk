<?php

use Illuminate\Support\Facades\Route;
use Modules\Contacts\Http\Controllers\ContactsController;

Route::get('/contacts', [ContactsController::class, 'overview'])->name('contacts');
Route::get('/contacts/suppliers', [ContactsController::class, 'suppliers'])->name('contacts.suppliers');
Route::get('/contacts/customers', [ContactsController::class, 'customers'])->name('contacts.customers');
Route::get('/contacts/carriers', [ContactsController::class, 'carriers'])->name('contacts.carriers');
Route::get('/contacts/message-templates', [ContactsController::class, 'messageTemplates'])->name('contacts.message-templates');
