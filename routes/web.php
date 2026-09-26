<?php

use App\Http\Controllers\ImapConnectionTestController;
use App\Http\Controllers\LaunchController;
use App\Http\Controllers\MailAccountController;
use App\Http\Controllers\MailboxController;
use Illuminate\Support\Facades\Route;

Route::get('/', LaunchController::class)->name('home');
Route::get('connections/imap', [ImapConnectionTestController::class, 'create'])->name('connections.imap.create');
Route::post('connections/imap', [ImapConnectionTestController::class, 'store'])->middleware('throttle:6,1')->name('connections.imap.store');
Route::get('dashboard', [MailboxController::class, 'index'])->name('dashboard');
Route::resource('accounts', MailAccountController::class)->only(['index', 'create', 'store']);
Route::post('accounts/{account}/test-connection', [MailAccountController::class, 'test'])->name('accounts.test-connection');

require __DIR__.'/settings.php';
