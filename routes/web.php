<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('pages.guest.landing'))->name('landing');

Route::get('/dashboard', fn () => view('pages.app.dashboard'))->name('dashboard');
