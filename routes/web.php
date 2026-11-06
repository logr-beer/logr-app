<?php

use App\Http\Controllers\DiscordOAuthController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\LogrCallbackController;
use App\Livewire\Admin\SystemInfo;
use App\Livewire\Setup;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('setup', Setup::class)->name('setup');

Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('profile', 'profile')->name('profile');
    Route::middleware('admin')->group(function () {
        Route::view('admin/api', 'admin.api')->name('admin.api');
        Route::view('admin/notifications', 'admin.notifications')->name('admin.notifications');
        Route::get('admin/system', SystemInfo::class)->name('admin.system');
    });

    Route::view('beers', 'beers.index')->name('beers.index');
    Route::view('beers/create', 'beers.create')->name('beers.create');
    Route::get('export', ExportController::class)->name('export');
    Route::view('beers/inventory', 'beers.inventory')->name('beers.inventory');
    Route::view('beers/{beer}', 'beers.show')->name('beers.show');
    Route::view('beers/{beer}/edit', 'beers.edit')->name('beers.edit');

    Route::view('collections', 'collections.index')->name('collections.index');
    Route::view('collections/{collection}', 'collections.show')->name('collections.show');

    Route::view('checkins', 'checkins.index')->name('checkins.index');
    Route::view('checkins/create', 'checkins.create')->name('checkins.create');
    Route::view('checkins/{checkin}/edit', 'checkins.edit')->name('checkins.edit');
    Route::view('locations/venues', 'locations.index', ['type' => 'venue'])->name('locations.venues');
    Route::view('locations/venues/{venue}', 'locations.show-venue')->name('venues.show');
    Route::view('locations/breweries', 'locations.index', ['type' => 'brewery'])->name('locations.breweries');
    Route::view('locations/breweries/{brewery}', 'locations.show-brewery')->name('breweries.show');
    Route::view('locations/stores', 'locations.index', ['type' => 'store'])->name('locations.stores');
    Route::view('locations/stores/{store}', 'locations.show-store')->name('stores.show');
    Route::view('stats', 'rankings')->name('stats');
    Route::view('import', 'import')->name('import');

    Route::middleware('admin')->group(function () {
        Route::get('logr/connect', [LogrCallbackController::class, 'redirect'])->name('logr.connect');
        Route::get('logr/callback', [LogrCallbackController::class, 'callback'])->name('logr.callback');

        Route::get('discord/link', [DiscordOAuthController::class, 'redirect'])->name('discord.link');
        Route::get('discord/callback', [DiscordOAuthController::class, 'callback'])->name('discord.callback');
        Route::post('discord/unlink', [DiscordOAuthController::class, 'unlink'])->name('discord.unlink');
    });
});

require __DIR__.'/auth.php';
