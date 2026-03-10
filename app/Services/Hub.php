<?php

namespace App\Services;

use App\Models\Checkin;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Hub
{
    public static function sendCheckin(Checkin $checkin, User $user): bool
    {
        $bots = collect($user->getData('discord_bots') ?? [])
            ->filter(fn ($b) => !empty($b['hub_url']) && !empty($b['hub_api_key']) && !empty($b['guild_id']) && !empty($b['publish_checkins']));

        if ($bots->isEmpty()) {
            return false;
        }

        $checkin->loadMissing(['beer.brewery', 'venue']);
        $beer = $checkin->beer;

        $payload = [
            'beer_name' => $beer->name,
            'brewery' => $beer->brewery?->name,
            'rating' => $checkin->rating,
            'serving' => $checkin->serving_type ? ucfirst($checkin->serving_type) : null,
            'notes' => $checkin->notes,
            'venue' => $checkin->venue?->name ?? $checkin->location,
            'style' => $beer->style ? implode(', ', $beer->style) : null,
            'abv' => $beer->abv,
            'user' => $user->name,
            'beer_image' => $beer->photo_path ? url(Storage::url($beer->photo_path)) : null,
            'username' => 'Logr',
            'avatar_url' => null,
        ];

        $sent = false;
        foreach ($bots as $bot) {
            if (static::post($bot, 'checkin', $payload)) {
                $sent = true;
            }
        }

        return $sent;
    }

    public static function sendPurchase(Inventory $inventory, User $user): bool
    {
        $bots = collect($user->getData('discord_bots') ?? [])
            ->filter(fn ($b) => !empty($b['hub_url']) && !empty($b['hub_api_key']) && !empty($b['guild_id']) && !empty($b['publish_purchases']));

        if ($bots->isEmpty()) {
            return false;
        }

        $inventory->loadMissing(['beer.brewery']);
        $beer = $inventory->beer;

        $payload = [
            'beer_name' => $beer->name,
            'brewery' => $beer->brewery?->name,
            'quantity' => $inventory->quantity,
            'storage_location' => $inventory->storage_location,
            'purchase_location' => $inventory->purchase_location,
            'is_gift' => $inventory->is_gift,
            'style' => $beer->style ? implode(', ', $beer->style) : null,
            'abv' => $beer->abv,
            'user' => $user->name,
            'beer_image' => $beer->photo_path ? url(Storage::url($beer->photo_path)) : null,
            'username' => 'Logr',
            'avatar_url' => null,
        ];

        $sent = false;
        foreach ($bots as $bot) {
            if (static::post($bot, 'purchase', $payload)) {
                $sent = true;
            }
        }

        return $sent;
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
                ->get(rtrim($hubUrl, '/') . '/api/guilds');

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
                ->get(rtrim($hubUrl, '/') . '/api/guilds/' . $guildId . '/channels');

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
                ->put(rtrim($hubUrl, '/') . '/api/guilds/' . $guildId . '/channel', [
                    'channel_id' => $channelId,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Hub updateChannel error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    protected static function post(array $bot, string $type, array $payload): bool
    {
        try {
            $response = Http::withToken($bot['hub_api_key'])
                ->accept('application/json')
                ->timeout(15)
                ->post(rtrim($bot['hub_url'], '/') . '/api/post', [
                    'guild_id' => $bot['guild_id'],
                    'type' => $type,
                    'payload' => $payload,
                ]);

            if (!$response->successful()) {
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
