<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureAppInstalled;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustHosts(at: fn () => array_map(
            fn (string $host) => '^'.preg_quote($host).'$',
            array_filter(array_map('trim', explode(',', (string) config('app.trusted_hosts')))),
        ), subdomains: false);

        $middleware->web(append: [
            EnsureAppInstalled::class,
        ]);

        $middleware->alias([
            'admin' => EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
