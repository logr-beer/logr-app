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
            ->filter(fn ($w) => ! empty($w['url']));

        if ($webhooks->isEmpty()) {
            return false;
        }

        $checkin->loadMissing(['beer.brewery', 'venue', 'photos']);
        $beer = $checkin->beer;

        $imagePath = $checkin->photos->first()?->photo_path ?? $beer->photo_path ?? $beer->brewery?->logo_path;

        $embed = static::buildCheckinEmbed([
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
        ], $checkin->created_at->toIso8601String());

        $localImage = $imagePath ? static::resolveLocalPath($imagePath) : null;

        $sent = false;
        foreach ($webhooks as $webhook) {
            if (static::send($webhook['url'], $embed, $localImage)) {
                $sent = true;
            }
        }

        return $sent;
    }

    public static function sendPurchase(Inventory $inventory, User $user): bool
    {
        $webhooks = collect($user->getData('discord_webhooks') ?? [])
            ->filter(fn ($w) => ! empty($w['url']));

        if ($webhooks->isEmpty()) {
            return false;
        }

        $inventory->loadMissing(['beer.brewery', 'store']);
        $beer = $inventory->beer;

        $imagePath = $beer->photo_path ?? $beer->brewery?->logo_path;

        $embed = static::buildPurchaseEmbed([
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
        ]);

        $localImage = $imagePath ? static::resolveLocalPath($imagePath) : null;

        $sent = false;
        foreach ($webhooks as $webhook) {
            if (static::send($webhook['url'], $embed, $localImage)) {
                $sent = true;
            }
        }

        return $sent;
    }

    /**
     * Build a check-in embed from a payload (same structure the bot uses).
     */
    public static function buildCheckinEmbed(array $p, ?string $timestamp = null): array
    {
        $description = "**{$p['beer_name']}** by {$p['brewery']}";

        if ($p['notes'] ?? null) {
            $description .= "\n> {$p['notes']}";
        }

        $fields = [];
        if ($p['style'] ?? null) {
            $fields[] = ['name' => 'Style', 'value' => $p['style'], 'inline' => true];
        }
        if ($p['abv'] ?? null) {
            $fields[] = ['name' => 'ABV', 'value' => "{$p['abv']}%", 'inline' => true];
        }
        if ($p['ibu'] ?? null) {
            $fields[] = ['name' => 'IBU', 'value' => (string) $p['ibu'], 'inline' => true];
        }
        if ($p['rating'] ?? null) {
            $rating = $p['rating'];
            $stars = str_repeat("\u{2B50}", (int) $rating);
            if ($rating - (int) $rating >= 0.5) {
                $stars .= "\u{2B50}";
            }
            $fields[] = ['name' => 'Rating', 'value' => "{$stars} {$rating}/5", 'inline' => true];
        }
        if ($p['serving'] ?? null) {
            $fields[] = ['name' => 'Serving', 'value' => $p['serving'], 'inline' => true];
        }
        if ($p['venue'] ?? null) {
            $fields[] = ['name' => 'Venue', 'value' => $p['venue'], 'inline' => true];
        }

        $embed = [
            'title' => "Check-in for {$p['user']}",
            'description' => $description,
            'color' => 0xF59E0B,
            'fields' => $fields,
            'timestamp' => $timestamp ?? now()->toIso8601String(),
            'footer' => ['text' => "Logr \u{2022} by {$p['user']}"],
        ];

        if ($p['beer_image'] ?? null) {
            $embed['thumbnail'] = ['url' => $p['beer_image']];
        }

        return $embed;
    }

    /**
     * Build an inventory embed from a payload (same structure the bot uses).
     */
    public static function buildPurchaseEmbed(array $p): array
    {
        $description = "**{$p['beer_name']}** by {$p['brewery']}";

        if ($p['is_gift'] ?? false) {
            $description .= "\nThis was a gift!";
        }

        $fields = [];
        if ($p['style'] ?? null) {
            $fields[] = ['name' => 'Style', 'value' => $p['style'], 'inline' => true];
        }
        if ($p['abv'] ?? null) {
            $fields[] = ['name' => 'ABV', 'value' => "{$p['abv']}%", 'inline' => true];
        }
        if ($p['ibu'] ?? null) {
            $fields[] = ['name' => 'IBU', 'value' => (string) $p['ibu'], 'inline' => true];
        }
        $fields[] = ['name' => 'Quantity', 'value' => (string) $p['quantity'], 'inline' => true];
        if ($p['storage_location'] ?? null) {
            $fields[] = ['name' => 'Storage', 'value' => $p['storage_location'], 'inline' => true];
        }
        if ($p['purchase_location'] ?? null) {
            $fields[] = ['name' => 'From', 'value' => $p['purchase_location'], 'inline' => true];
        }

        $embed = [
            'title' => "New Beer for {$p['user']}",
            'description' => $description,
            'color' => 0x3B82F6,
            'fields' => $fields,
            'timestamp' => now()->toIso8601String(),
            'footer' => ['text' => "Logr \u{2022} by {$p['user']}"],
        ];

        if ($p['beer_image'] ?? null) {
            $embed['thumbnail'] = ['url' => $p['beer_image']];
        }

        return $embed;
    }

    protected static function resolveLocalPath(string $storagePath): ?string
    {
        $disk = Storage::disk('public');
        if ($disk->exists($storagePath)) {
            return $disk->path($storagePath);
        }

        return null;
    }

    protected static function send(string $webhookUrl, array $embed, ?string $imagePath = null): bool
    {
        try {
            if ($imagePath && file_exists($imagePath)) {
                $filename = basename($imagePath);
                $embed['thumbnail'] = ['url' => "attachment://{$filename}"];

                $response = Http::timeout(15)
                    ->attach('file', file_get_contents($imagePath), $filename)
                    ->post($webhookUrl, [
                        'payload_json' => json_encode([
                            'username' => 'Logr Bot',
                            'avatar_url' => url('/img/logr-discord.png'),
                            'embeds' => [$embed],
                        ]),
                    ]);
            } else {
                $response = Http::timeout(15)->post($webhookUrl, [
                    'username' => 'Logr Bot',
                    'avatar_url' => url('/img/logr-discord.png'),
                    'embeds' => [$embed],
                ]);
            }

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
