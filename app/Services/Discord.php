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
            'beer_image' => $imagePath ? url(Storage::url($imagePath)) : null,
        ], $checkin->created_at->toIso8601String());

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

        $imagePath = $beer->photo_path ?? $beer->brewery?->logo_path;

        $embed = static::buildPurchaseEmbed([
            'beer_name' => $beer->name,
            'brewery' => $beer->brewery?->name,
            'quantity' => $inventory->quantity,
            'storage_location' => $inventory->storage_location,
            'purchase_location' => $inventory->purchase_location,
            'is_gift' => $inventory->is_gift,
            'style' => $beer->style ? implode(', ', $beer->style) : null,
            'abv' => $beer->abv,
            'ibu' => $beer->ibu,
            'user' => $user->name,
            'beer_image' => $imagePath ? url(Storage::url($imagePath)) : null,
        ]);

        $sent = false;
        foreach ($webhooks as $webhook) {
            if (static::send($webhook['url'], $embed)) {
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
        $description = "**{$p['beer_name']}**";
        if ($p['brewery'] ?? null) {
            $description .= " by **{$p['brewery']}**";
        }
        $description .= "\n";

        if ($p['rating'] ?? null) {
            $rating = $p['rating'];
            $stars = str_repeat("\u{2B50}", (int) $rating);
            if ($rating - (int) $rating >= 0.5) {
                $stars .= "\u{2B50}";
            }
            $description .= "\n**Rating:** {$rating} / 5 {$stars}";
        }

        if ($p['notes'] ?? null) {
            $description .= "\n\n**Review:**\n> {$p['notes']}";
        }

        if ($p['venue'] ?? null) {
            $description .= "\n\n**Venue:**\n{$p['venue']}";
        }

        if ($p['style'] ?? null) {
            $description .= "\n\n**Style:**\n{$p['style']}";
        }

        $fields = [];
        if ($p['abv'] ?? null) {
            $fields[] = ['name' => 'ABV', 'value' => "{$p['abv']}%", 'inline' => true];
        }
        if ($p['ibu'] ?? null) {
            $fields[] = ['name' => 'IBU', 'value' => (string) $p['ibu'], 'inline' => true];
        }
        if ($p['serving'] ?? null) {
            $fields[] = ['name' => 'Serving', 'value' => $p['serving'], 'inline' => true];
        }

        $embed = [
            'title' => "Check-in: {$p['user']}",
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
        $description = "**{$p['beer_name']}**";
        if ($p['brewery'] ?? null) {
            $description .= " by {$p['brewery']}";
        }
        $description .= "\n\n**Quantity:** {$p['quantity']}";
        if ($p['storage_location'] ?? null) {
            $description .= "\n**Storage:** {$p['storage_location']}";
        }
        if ($p['purchase_location'] ?? null) {
            $description .= "\n**From:** {$p['purchase_location']}";
        }
        if ($p['is_gift'] ?? false) {
            $description .= "\n\nThis was a gift!";
        }

        $fields = [];
        if ($p['style'] ?? null) {
            $fields[] = ['name' => 'Style', 'value' => $p['style'], 'inline' => true];
        }
        if ($p['abv'] ?? null) {
            $fields[] = ['name' => 'ABV', 'value' => "{$p['abv']}%", 'inline' => true];
        }

        $embed = [
            'title' => "Inventory: {$p['user']}",
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
