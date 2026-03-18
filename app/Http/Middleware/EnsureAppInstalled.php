<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        // No users yet — force setup (except on the setup page and Livewire update requests)
        if (User::count() === 0 && !$request->routeIs('setup') && !$request->is('livewire/*')) {
            return redirect()->route('setup');
        }

        return $next($request);
    }
}
