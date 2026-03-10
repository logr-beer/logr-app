<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogrCallbackController extends Controller
{
    public function redirect()
    {
        $hubUrl = rtrim(config('services.logr.hub_url'), '/');
        $callbackUrl = route('logr.callback');

        $state = bin2hex(random_bytes(16));
        session(['logr_state' => $state]);

        return redirect("{$hubUrl}/oauth/authorize?" . http_build_query([
            'callback_url' => $callbackUrl,
            'state' => $state,
        ]));
    }

    public function callback(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string',
            'guild_id' => 'required|string',
            'guild_name' => 'required|string',
            'state' => 'required|string',
        ]);

        if ($request->state !== session('logr_state')) {
            return redirect()->route('profile')->with('error', 'Invalid state. Please try again.');
        }

        session()->forget('logr_state');

        $user = Auth::user();
        $hubUrl = rtrim(config('services.logr.hub_url'), '/');
        $bots = $user->getData('discord_bots') ?? [];

        // Check if this guild is already connected
        $exists = collect($bots)->contains(fn ($b) =>
            $b['hub_url'] === $hubUrl && $b['guild_id'] === $request->guild_id
        );

        if (!$exists) {
            $bots[] = [
                'label' => $request->guild_name,
                'hub_url' => $hubUrl,
                'hub_api_key' => $request->api_key,
                'guild_id' => $request->guild_id,
                'guild_name' => $request->guild_name,
                'channel_name' => $request->channel_name,
                'publish_checkins' => true,
                'publish_purchases' => true,
            ];

            $user->setData('discord_bots', $bots);
            $user->save();
        }

        return redirect()->route('profile')->with('message', "Connected to {$request->guild_name}!");
    }
}
