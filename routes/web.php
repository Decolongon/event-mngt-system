<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified', 'role:organizer'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware(['auth', 'verified', 'role:attendee'])->group(function () {
   Route::livewire('attendee/dashboard', 'pages::attendees.dashboard')->name('attendee.dashboard');
});

require __DIR__.'/settings.php';
