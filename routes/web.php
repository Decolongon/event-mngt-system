<?php

use App\Enums\EventPermissionEnum;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified', 'role:organizer'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('organizer/event', 'pages::organizers.event')->name('organizer.event')->middleware('permission:'.EventPermissionEnum::VIEW_EVENTS->value);
    Route::livewire('organizer/ticket-type', 'pages::organizers.ticket-type')->name('organizer.ticket-type');
});

Route::middleware(['auth', 'verified', 'role:attendee'])->group(function () {
    Route::livewire('attendee/dashboard', 'pages::attendees.dashboard')->name('attendee.dashboard');
});

require __DIR__.'/settings.php';
