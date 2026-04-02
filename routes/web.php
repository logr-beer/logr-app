<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('setup', \App\Livewire\Setup::class)->name('setup');

Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('profile', 'profile')->name('profile');
    Route::view('admin/api', 'admin.api')->name('admin.api');
    Route::view('admin/notifications', 'admin.notifications')->name('admin.notifications');
    Route::get('admin/system', \App\Livewire\Admin\SystemInfo::class)->name('admin.system');

    Route::view('beers', 'beers.index')->name('beers.index');
    Route::view('beers/create', 'beers.create')->name('beers.create');
    Route::get('beers/export', [\App\Http\Controllers\ExportController::class, 'beers'])->name('beers.export');
    Route::view('beers/inventory', 'beers.inventory')->name('beers.inventory');
    Route::view('beers/{beer}', 'beers.show')->name('beers.show');
    Route::view('beers/{beer}/edit', 'beers.edit')->name('beers.edit');

    Route::view('collections', 'collections.index')->name('collections.index');
    Route::view('collections/create', 'collections.create')->name('collections.create');
    Route::view('collections/{collection}', 'collections.show')->name('collections.show');

    Route::view('checkins', 'checkins.index')->name('checkins.index');
    Route::view('checkins/create', 'checkins.create')->name('checkins.create');
    Route::get('checkins/{checkin}/edit', fn ($checkin) => view('checkins.edit', ['checkin' => $checkin]))->name('checkins.edit');
    Route::get('checkins/export', [\App\Http\Controllers\ExportController::class, 'checkins'])->name('checkins.export');
    Route::view('locations/venues', 'venues.index')->name('locations.venues');
    Route::view('locations/venues/{venue}', 'venues.show')->name('venues.show');
    Route::view('locations/breweries', 'locations')->name('locations.breweries');
    Route::view('stats', 'rankings')->name('stats');
    Route::view('import', 'import')->name('import');

    Route::get('logr/connect', [\App\Http\Controllers\LogrCallbackController::class, 'redirect'])->name('logr.connect');
    Route::get('logr/callback', [\App\Http\Controllers\LogrCallbackController::class, 'callback'])->name('logr.callback');
});

require __DIR__.'/auth.php';
