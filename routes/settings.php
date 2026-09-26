<?php

use Illuminate\Support\Facades\Route;

Route::redirect('settings', '/accounts');
Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');
