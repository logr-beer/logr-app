<?php

namespace App\Services;

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
            ->filter(fn ($w) => ! empty($w['url']) && ! empty($w['publish_checkins']));

        if ($webhooks->isEmpty()) {
            return false;
        }

        $checkin->loadMissing(['beer.brewery', 'venue', 'photos']);
        $beer = $checkin->beer;

        $description = '';
        if ($beer->brewery) {
            $description .= "by {$beer->brewery->name}\n";
        }

        if ($checkin->rating) {
            $stars = str_repeat("\u{2B50}", (int) $checkin->rating);
            if ($checkin->rating - (int) $checkin->rating >= 0.5) {
                $stars .= "\u{2B50}";
            }
            $description .= "\nRating: **{$checkin->rating}** / 5 {$stars}";
        }

        if ($checkin->notes) {
            $description .= "\n\n> {$checkin->notes}";
        }

        if ($checkin->venue) {
            $description .= "\n\nVenue: {$checkin->venue->name}";
        } elseif ($checkin->location) {
            $description .= "\n\nLocation: {$checkin->location}";
        }

        $fields = [];
        if ($beer->style) {
            $fields[] = ['name' => 'Style', 'value' => implode(', ', $beer->style), 'inline' => true];
        }
        if ($beer->abv) {
            $fields[] = ['name' => 'ABV', 'value' => "{$beer->abv}%", 'inline' => true];
        }
        if ($beer->ibu || $checkin->serving_type) {
            $fields[] = ['name' => "\u{200b}", 'value' => "\u{200b}", 'inline' => false];
        }
        if ($beer->ibu) {
            $fields[] = ['name' => 'IBU', 'value' => (string) $beer->ibu, 'inline' => true];
        }
        if ($checkin->serving_type) {
            $fields[] = ['name' => 'Serving', 'value' => ucfirst($checkin->serving_type), 'inline' => true];
        }

        $embed = [
            'title' => "Check-in: {$beer->name}",
            'description' => $description,
            'color' => 0xF59E0B, // amber-500
            'fields' => $fields,
            'timestamp' => $checkin->created_at->toIso8601String(),
            'footer' => ['text' => 'Logr'],
        ];

        // Image priority: checkin photo > beer label > brewery logo
        $imageUrl = static::resolveImageUrl(
            $checkin->photos->first()?->photo_path,
            $beer->photo_path,
            $beer->brewery?->logo_path,
        );

        if ($imageUrl) {
            $embed['thumbnail'] = ['url' => $imageUrl];
        }

        $sent = false;
        foreach ($webhooks as $webhook) {
            if (static::send($webhook['url'], $embed)) {
                $sent = true;
            }
        }

        return $sent;
    }

    public static function sendPurchase(Inventory $inventory, User $user): bool
    {
        $webhooks = collect($user->getData('discord_webhooks') ?? [])
            ->filter(fn ($w) => ! empty($w['url']) && ! empty($w['publish_purchases']));

        if ($webhooks->isEmpty()) {
            return false;
        }

        $inventory->loadMissing(['beer.brewery']);
        $beer = $inventory->beer;

        $description = "**{$beer->name}**";
        if ($beer->brewery) {
            $description .= " by {$beer->brewery->name}";
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

        $fields[] = ['name' => 'Quantity', 'value' => (string) $inventory->quantity, 'inline' => true];
        if ($inventory->storage_location) {
            $fields[] = ['name' => 'Storage', 'value' => $inventory->storage_location, 'inline' => true];
        }
        if ($inventory->purchase_location) {
            $fields[] = ['name' => 'From', 'value' => $inventory->purchase_location, 'inline' => true];
        }
        if ($inventory->is_gift) {
            $description .= "\n\nThis was a gift!";
        }

        $embed = [
            'title' => "Added to Inventory: {$beer->name}",
            'description' => $description,
            'color' => 0x3B82F6, // blue-500
            'fields' => $fields,
            'timestamp' => now()->toIso8601String(),
            'footer' => ['text' => 'Logr'],
        ];

        // Image priority: beer label > brewery logo
        $imageUrl = static::resolveImageUrl(
            null,
            $beer->photo_path,
            $beer->brewery?->logo_path,
        );

        if ($imageUrl) {
            $embed['thumbnail'] = ['url' => $imageUrl];
        }

        $sent = false;
        foreach ($webhooks as $webhook) {
            if (static::send($webhook['url'], $embed)) {
                $sent = true;
            }
        }

        return $sent;
    }

    protected static function resolveImageUrl(?string ...$paths): ?string
    {
        foreach ($paths as $path) {
            if ($path) {
                return url(Storage::url($path));
            }
        }

        return null;
    }

    protected static function send(string $webhookUrl, array $embed): bool
    {
        try {
            $response = Http::timeout(15)->post($webhookUrl, [
                'username' => 'Logr Bot',
                'avatar_url' => url('/img/logr-discord.png'),
                'embeds' => [$embed],
            ]);

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
