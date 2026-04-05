<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogrCallbackController extends Controller
{
    public function redirect()
    {
        $user = Auth::user();

        if (! $user->is_admin) {
            return redirect()->route('admin.notifications')->with('error', 'Only admins can connect the Discord bot.');
        }

        $hubUrl = rtrim(config('services.logr.discord_url'), '/');
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
            'api_key' => 'required|string|max:255',
            'guild_id' => 'required|string|max:50',
            'guild_name' => 'required|string|max:100',
            'channel_name' => 'nullable|string|max:100',
            'state' => 'required|string|max:64',
        ]);

        if ($request->state !== session('logr_state')) {
            return redirect()->route('admin.notifications')->with('error', 'Invalid state. Please try again.');
        }

        session()->forget('logr_state');

        $user = Auth::user();

        if (! $user->is_admin) {
            return redirect()->route('admin.notifications')->with('error', 'Only admins can connect the Discord bot.');
        }

        $hubUrl = rtrim(config('services.logr.discord_url'), '/');
        $bots = Setting::get('discord_bots', []);

        // Check if this guild is already connected
        $exists = collect($bots)->contains(fn ($b) =>
            $b['hub_url'] === $hubUrl && $b['guild_id'] === $request->guild_id
        );

        if (! $exists) {
            $bots[] = [
                'hub_url' => $hubUrl,
                'hub_api_key' => $request->api_key,
                'guild_id' => $request->guild_id,
                'guild_name' => $request->guild_name,
                'channel_name' => $request->channel_name,
            ];

            Setting::set('discord_bots', $bots);
        }

        // Enable publishing for this server for the connecting admin
        $prefs = $user->getData('discord_bot_prefs') ?? [];
        $prefs[$request->guild_id] = [
            'publish_checkins' => true,
            'publish_purchases' => true,
        ];
        $user->setData('discord_bot_prefs', $prefs);
        $user->save();

        return redirect()->route('admin.notifications')->with('message', "Connected to {$request->guild_name}!");
    }
}
