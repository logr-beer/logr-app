<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordOAuthController extends Controller
{
    public function redirect()
    {
        $bot = $this->firstBot();

        if (! $bot) {
            return redirect()->route('admin.notifications')->with('error', 'No Discord bot configured.');
        }

        $response = Http::withToken($bot['hub_api_key'])
            ->accept('application/json')
            ->timeout(15)
            ->post(rtrim($bot['hub_url'], '/').'/api/users/link-token', [
                'user_identifier' => Auth::user()->name,
                'callback_url' => route('discord.callback'),
            ]);

        if (! $response->successful()) {
            Log::warning('Discord link token request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return redirect()->route('admin.notifications')->with('error', 'Failed to start Discord linking.');
        }

        return redirect($response->json('link_url'));
    }

    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('admin.notifications')->with('error', 'Discord authorization was cancelled.');
        }

        $user = Auth::user();

        if ($request->has('discord_username')) {
            $user->setData('discord_username', $request->input('discord_username'));
            $user->setData('discord_user_id', $request->input('discord_id'));
            $user->save();
        }

        return redirect()->route('admin.notifications')
            ->with('message', "Linked Discord account: {$request->input('discord_username')}");
    }

    public function unlink()
    {
        $user = Auth::user();
        $bot = $this->firstBot();

        if ($bot) {
            Http::withToken($bot['hub_api_key'])
                ->accept('application/json')
                ->timeout(15)
                ->post(rtrim($bot['hub_url'], '/').'/api/users/unlink', [
                    'user_identifier' => $user->name,
                ]);
        }

        $user->setData('discord_user_id', null);
        $user->setData('discord_username', null);
        $user->setData('discord_avatar', null);
        $user->save();

        return redirect()->route('admin.notifications')->with('message', 'Discord account unlinked.');
    }

    protected function firstBot(): ?array
    {
        $bots = Setting::get('discord_bots', []);

        return $bots[0] ?? null;
    }
}
