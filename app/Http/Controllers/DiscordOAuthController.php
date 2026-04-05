<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordOAuthController extends Controller
{
    public function redirect()
    {
        $clientId = config('services.discord.client_id');

        if (! $clientId) {
            return redirect()->route('admin.notifications')->with('error', 'Discord OAuth is not configured.');
        }

        $state = bin2hex(random_bytes(16));
        session(['discord_oauth_state' => $state]);

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => route('discord.callback'),
            'response_type' => 'code',
            'scope' => 'identify',
            'state' => $state,
        ]);

        return redirect("https://discord.com/api/oauth2/authorize?{$params}");
    }

    public function callback(Request $request)
    {
        if ($request->state !== session('discord_oauth_state')) {
            return redirect()->route('admin.notifications')->with('error', 'Invalid state. Please try again.');
        }

        session()->forget('discord_oauth_state');

        if ($request->has('error')) {
            return redirect()->route('admin.notifications')->with('error', 'Discord authorization was cancelled.');
        }

        $request->validate([
            'code' => 'required|string',
        ]);

        // Exchange the code for an access token
        $tokenResponse = Http::asForm()->post('https://discord.com/api/oauth2/token', [
            'client_id' => config('services.discord.client_id'),
            'client_secret' => config('services.discord.client_secret'),
            'grant_type' => 'authorization_code',
            'code' => $request->code,
            'redirect_uri' => route('discord.callback'),
        ]);

        if (! $tokenResponse->successful()) {
            Log::warning('Discord OAuth token exchange failed', ['status' => $tokenResponse->status(), 'body' => $tokenResponse->body()]);

            return redirect()->route('admin.notifications')->with('error', 'Failed to connect Discord account.');
        }

        $accessToken = $tokenResponse->json('access_token');

        // Fetch the user's Discord profile
        $userResponse = Http::withToken($accessToken)->get('https://discord.com/api/v10/users/@me');

        if (! $userResponse->successful()) {
            Log::warning('Discord OAuth user fetch failed', ['status' => $userResponse->status()]);

            return redirect()->route('admin.notifications')->with('error', 'Failed to fetch Discord profile.');
        }

        $discord = $userResponse->json();

        $user = Auth::user();
        $user->setData('discord_user_id', $discord['id']);
        $user->setData('discord_username', $discord['global_name'] ?? $discord['username']);
        $user->setData('discord_avatar', $discord['avatar']);
        $user->save();

        return redirect()->route('admin.notifications')->with('message', "Linked Discord account: {$user->getData('discord_username')}");
    }

    public function unlink()
    {
        $user = Auth::user();
        $user->setData('discord_user_id', null);
        $user->setData('discord_username', null);
        $user->setData('discord_avatar', null);
        $user->save();

        return redirect()->route('admin.notifications')->with('message', 'Discord account unlinked.');
    }
}
