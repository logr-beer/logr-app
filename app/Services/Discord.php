<?php

namespace App\Services;

use App\Models\Beer;
use App\Models\Checkin;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Discord
{
    public static function sendCheckin(Checkin $checkin, User $user): bool
    {
        $webhooks = collect($user->getData('discord_webhooks') ?? [])
            ->filter(fn ($w) => !empty($w['url']) && !empty($w['publish_checkins']));

        if ($webhooks->isEmpty()) {
            return false;
        }

        $checkin->loadMissing(['beer.brewery', 'venue']);
        $beer = $checkin->beer;

        $stars = str_repeat("\u{2B50}", (int) $checkin->rating);
        if ($checkin->rating - (int) $checkin->rating >= 0.5) {
            $stars .= "\u{2B50}";
        }

        $description = "**{$beer->name}**";
        if ($beer->brewery) {
            $description .= " by {$beer->brewery->name}";
        }
        $description .= "\n\nRating: **{$checkin->rating}** / 5 {$stars}";

        if ($checkin->serving_type) {
            $description .= "\nServing: " . ucfirst($checkin->serving_type);
        }
        if ($checkin->venue) {
            $description .= "\nVenue: {$checkin->venue->name}";
        } elseif ($checkin->location) {
            $description .= "\nLocation: {$checkin->location}";
        }
        if ($checkin->notes) {
            $description .= "\n\n> {$checkin->notes}";
        }

        $fields = [];
        if ($beer->style) {
            $fields[] = ['name' => 'Style', 'value' => implode(', ', $beer->style), 'inline' => true];
        }
        if ($beer->abv) {
            $fields[] = ['name' => 'ABV', 'value' => "{$beer->abv}%", 'inline' => true];
        }
        if ($beer->ibu) {
            $fields[] = ['name' => 'IBU', 'value' => (string) $beer->ibu, 'inline' => true];
        }

        $embed = [
            'title' => "Check-in: {$beer->name}",
            'description' => $description,
            'color' => 0xF59E0B, // amber-500
            'fields' => $fields,
            'timestamp' => $checkin->created_at->toIso8601String(),
            'footer' => ['text' => 'Logr'],
        ];

        if ($beer->photo_path) {
            $photoUrl = url(Storage::url($beer->photo_path));
            $embed['thumbnail'] = ['url' => $photoUrl];
        }

        $sent = false;
        $identity = static::discordIdentity($user);
        foreach ($webhooks as $webhook) {
            if (static::send($webhook['url'], $embed, $identity)) {
                $sent = true;
            }
        }

        return $sent;
    }

    public static function sendPurchase(Inventory $inventory, User $user): bool
    {
        $webhooks = collect($user->getData('discord_webhooks') ?? [])
            ->filter(fn ($w) => !empty($w['url']) && !empty($w['publish_purchases']));

        if ($webhooks->isEmpty()) {
            return false;
        }

        $inventory->loadMissing(['beer.brewery']);
        $beer = $inventory->beer;

        $description = "**{$beer->name}**";
        if ($beer->brewery) {
            $description .= " by {$beer->brewery->name}";
        }
        $description .= "\n\nQuantity: **{$inventory->quantity}**";
        $description .= "\nStorage: {$inventory->storage_location}";

        if ($inventory->purchase_location) {
            $description .= "\nFrom: {$inventory->purchase_location}";
        }
        if ($inventory->is_gift) {
            $description .= "\nThis was a gift!";
        }

        $fields = [];
        if ($beer->style) {
            $fields[] = ['name' => 'Style', 'value' => implode(', ', $beer->style), 'inline' => true];
        }
        if ($beer->abv) {
            $fields[] = ['name' => 'ABV', 'value' => "{$beer->abv}%", 'inline' => true];
        }

        $embed = [
            'title' => "Added to Inventory: {$beer->name}",
            'description' => $description,
            'color' => 0x3B82F6, // blue-500
            'fields' => $fields,
            'timestamp' => now()->toIso8601String(),
            'footer' => ['text' => 'Logr'],
        ];

        if ($beer->photo_path) {
            $photoUrl = url(Storage::url($beer->photo_path));
            $embed['thumbnail'] = ['url' => $photoUrl];
        }

        $sent = false;
        $identity = static::discordIdentity($user);
        foreach ($webhooks as $webhook) {
            if (static::send($webhook['url'], $embed, $identity)) {
                $sent = true;
            }
        }

        return $sent;
    }

    protected static function discordIdentity(User $user): array
    {
        $username = $user->getData('discord_username');

        if (! $username) {
            return [];
        }

        $identity = ['username' => $username];
        $avatarUrl = Hub::discordAvatarUrl($user);

        if ($avatarUrl) {
            $identity['avatar_url'] = $avatarUrl;
        }

        return $identity;
    }

    protected static function send(string $webhookUrl, array $embed, array $identity = []): bool
    {
        try {
            $response = Http::post($webhookUrl, array_filter([
                'embeds' => [$embed],
                'username' => $identity['username'] ?? null,
                'avatar_url' => $identity['avatar_url'] ?? null,
            ]));

            if (! $response->successful()) {
                Log::warning('Discord webhook failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::warning('Discord webhook error', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
