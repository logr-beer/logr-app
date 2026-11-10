<?php

namespace App\Services;

use App\Models\Checkin;
use App\Models\Inventory;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PubDiscord
{
    /**
     * @param  array  $shareTargets  Targets from the form (see CheckinForm::buildTargetsForType); when empty, the stored bot prefs apply.
     */
    public static function sendCheckin(Checkin $checkin, User $user, array $shareTargets = []): bool
    {
        $bots = static::botsFor($user, 'publish_checkins', $shareTargets);

        if (empty($bots)) {
            return false;
        }

        $checkin->loadMissing(['beer.brewery', 'venue', 'photos']);
        $beer = $checkin->beer;

        // Image priority: checkin photo > beer label
        $imagePath = $checkin->photos->first()?->photo_path ?? $beer->photo_path;

        $payload = [
            'beer_name' => $beer->name,
            'brewery' => $beer->brewery?->name,
            'rating' => $checkin->rating,
            'serving' => $checkin->serving_type ? ucfirst($checkin->serving_type) : null,
            'notes' => $checkin->notes,
            'venue' => $checkin->venue?->name ?? $checkin->location,
            'style' => $beer->style ? implode(', ', $beer->style) : null,
            'abv' => $beer->abv,
            'ibu' => $beer->ibu,
            'user' => $user->name,
            'beer_image' => $imagePath ? url(Storage::url($imagePath)) : null,
        ];

        $sent = false;
        foreach ($bots as $bot) {
            $key = "checkin-{$checkin->id}-{$bot['guild_id']}";
            if (static::post($bot, 'checkin', $payload, $key)) {
                $sent = true;
            }
        }

        return $sent;
    }

    /**
     * @param  array  $shareTargets  Targets from the form (see CheckinForm::buildTargetsForType); when empty, the stored bot prefs apply.
     */
    public static function sendPurchase(Inventory $inventory, User $user, array $shareTargets = []): bool
    {
        $bots = static::botsFor($user, 'publish_purchases', $shareTargets);

        if (empty($bots)) {
            return false;
        }

        $inventory->loadMissing(['beer.brewery', 'store']);
        $beer = $inventory->beer;

        $payload = [
            'beer_name' => $beer->name,
            'brewery' => $beer->brewery?->name,
            'quantity' => $inventory->quantity,
            'storage_location' => $inventory->storage_location,
            'purchase_location' => $inventory->store?->name,
            'is_gift' => $inventory->is_gift,
            'style' => $beer->style ? implode(', ', $beer->style) : null,
            'abv' => $beer->abv,
            'ibu' => $beer->ibu,
            'user' => $user->name,
            'beer_image' => $beer->photo_path ? url(Storage::url($beer->photo_path)) : null,
        ];

        $sent = false;
        foreach ($bots as $bot) {
            $key = "inventory-{$inventory->id}-{$bot['guild_id']}";
            if (static::post($bot, 'purchase', $payload, $key)) {
                $sent = true;
            }
        }

        return $sent;
    }

    /**
     * Get all configured bots, keyed by their index in the discord_bots setting.
     */
    protected static function allBots(): array
    {
        $allBots = Setting::get('discord_bots', []);

        if (empty($allBots)) {
            return [];
        }

        return collect($allBots)
            ->filter(fn ($bot) => ! empty($bot['hub_url']) && ! empty($bot['hub_api_key']) && ! empty($bot['guild_id']))
            ->all();
    }

    /**
     * Bots to post to: the ticked ones when a selection was made, otherwise those the user enabled in their bot prefs.
     * Keys of allBots() are the indexes used for "bot_{i}" share targets.
     */
    protected static function botsFor(User $user, string $publishKey, array $shareTargets): array
    {
        $bots = collect(static::allBots());

        if (empty($shareTargets)) {
            $prefs = $user->getData('discord_bot_prefs') ?? [];

            return $bots->filter(fn ($bot) => ! empty($prefs[$bot['guild_id']][$publishKey]))->all();
        }

        $selected = Discord::enabledTargetKeys($shareTargets);

        return $bots->filter(fn ($bot, $i) => in_array("bot_{$i}", $selected, true))->all();
    }

    /**
     * Check if user has any bot publishing enabled.
     */
    public static function hasPublishing(User $user, string $publishKey): bool
    {
        $allBots = static::allBots();
        if (empty($allBots)) {
            return false;
        }

        $prefs = $user->getData('discord_bot_prefs') ?? [];

        return collect($allBots)
            ->contains(fn ($bot) => ! empty($prefs[$bot['guild_id']][$publishKey]));
    }

    /**
     * Fetch available guilds from a hub instance.
     */
    public static function fetchGuilds(string $hubUrl, string $apiKey): ?array
    {
        try {
            $response = Http::withToken($apiKey)
                ->accept('application/json')
                ->timeout(10)
                ->get(rtrim($hubUrl, '/').'/api/discord/guilds');

            if ($response->successful()) {
                return $response->json('data') ?? $response->json();
            }

            Log::warning('Hub fetchGuilds failed', ['status' => $response->status(), 'body' => $response->body()]);

            return null;
        } catch (\Exception $e) {
            Log::warning('Hub fetchGuilds error', ['error' => $e->getMessage()]);

            return null;
        }
    }

    public static function fetchChannels(string $hubUrl, string $apiKey, string $guildId): ?array
    {
        try {
            $response = Http::withToken($apiKey)
                ->accept('application/json')
                ->timeout(10)
                ->get(rtrim($hubUrl, '/').'/api/discord/guilds/'.$guildId.'/channels');

            if ($response->successful()) {
                return $response->json('channels') ?? [];
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('Hub fetchChannels error', ['error' => $e->getMessage()]);

            return null;
        }
    }

    public static function updateChannel(string $hubUrl, string $apiKey, string $guildId, string $channelId): bool
    {
        try {
            $response = Http::withToken($apiKey)
                ->accept('application/json')
                ->timeout(10)
                ->put(rtrim($hubUrl, '/').'/api/discord/guilds/'.$guildId.'/channel', [
                    'channel_id' => $channelId,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Hub updateChannel error', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public static function discordUsername(User $user): string
    {
        return $user->getData('discord_username') ?? $user->name;
    }

    public static function discordAvatarUrl(User $user): ?string
    {
        $userId = $user->getData('discord_user_id');
        $avatar = $user->getData('discord_avatar');

        if ($userId && $avatar) {
            return "https://cdn.discordapp.com/avatars/{$userId}/{$avatar}.png";
        }

        return null;
    }

    protected static function post(array $bot, string $type, array $payload, ?string $idempotencyKey = null): bool
    {
        try {
            $body = [
                'guild_id' => $bot['guild_id'],
                'type' => $type,
                'payload' => $payload,
            ];

            if ($idempotencyKey) {
                $body['idempotency_key'] = $idempotencyKey;
            }

            $response = Http::withToken($bot['hub_api_key'])
                ->accept('application/json')
                ->timeout(15)
                ->post(rtrim($bot['hub_url'], '/').'/api/discord/post', $body);

            // 409 = duplicate, already posted — treat as success
            if ($response->status() === 409) {
                return true;
            }

            if (! $response->successful()) {
                Log::warning('Hub post failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'guild_id' => $bot['guild_id'],
                ]);

                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::warning('Hub post error', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
