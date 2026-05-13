<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('setup', \App\Livewire\Setup::class)->name('setup');

Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('profile', 'profile')->name('profile');
    Route::middleware('admin')->group(function () {
        Route::view('admin/api', 'admin.api')->name('admin.api');
        Route::view('admin/notifications', 'admin.notifications')->name('admin.notifications');
        Route::get('admin/system', \App\Livewire\Admin\SystemInfo::class)->name('admin.system');
    });

    Route::view('beers', 'beers.index')->name('beers.index');
    Route::view('beers/create', 'beers.create')->name('beers.create');
    Route::get('beers/export', [\App\Http\Controllers\ExportController::class, 'beers'])->name('beers.export');
    Route::view('beers/inventory', 'beers.inventory')->name('beers.inventory');
    Route::view('beers/{beer}', 'beers.show')->name('beers.show');
    Route::view('beers/{beer}/edit', 'beers.edit')->name('beers.edit');

    Route::view('collections', 'collections.index')->name('collections.index');
    Route::view('collections/{collection}', 'collections.show')->name('collections.show');

    Route::view('checkins', 'checkins.index')->name('checkins.index');
    Route::view('checkins/create', 'checkins.create')->name('checkins.create');
    Route::view('checkins/{checkin}/edit', 'checkins.edit')->name('checkins.edit');
    Route::get('checkins/export', [\App\Http\Controllers\ExportController::class, 'checkins'])->name('checkins.export');
    Route::view('locations/venues', 'locations.index', ['type' => 'venue'])->name('locations.venues');
    Route::view('locations/venues/{venue}', 'locations.show-venue')->name('venues.show');
    Route::view('locations/breweries', 'locations.index', ['type' => 'brewery'])->name('locations.breweries');
    Route::view('locations/breweries/{brewery}', 'locations.show-brewery')->name('breweries.show');
    Route::view('locations/stores', 'locations.index', ['type' => 'store'])->name('locations.stores');
    Route::view('locations/stores/{store}', 'locations.show-store')->name('stores.show');
    Route::view('stats', 'rankings')->name('stats');
    Route::view('import', 'import')->name('import');

    Route::middleware('admin')->group(function () {
        Route::get('logr/connect', [\App\Http\Controllers\LogrCallbackController::class, 'redirect'])->name('logr.connect');
        Route::get('logr/callback', [\App\Http\Controllers\LogrCallbackController::class, 'callback'])->name('logr.callback');

        Route::get('discord/link', [\App\Http\Controllers\DiscordOAuthController::class, 'redirect'])->name('discord.link');
        Route::get('discord/callback', [\App\Http\Controllers\DiscordOAuthController::class, 'callback'])->name('discord.callback');
        Route::post('discord/unlink', [\App\Http\Controllers\DiscordOAuthController::class, 'unlink'])->name('discord.unlink');
    });
});

require __DIR__.'/auth.php';
