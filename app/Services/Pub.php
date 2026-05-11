<?php

namespace App\Services;

use App\Models\Checkin;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Pub
{
    public static function sendCheckin(Checkin $checkin, User $user): bool
    {
        if (! $user->getData('share_checkin_data')) {
            return false;
        }

        $pubUrl = config('services.logr.pub_url');
        if (! $pubUrl) {
            return false;
        }

        $checkin->loadMissing(['beer.brewery']);
        $beer = $checkin->beer;

        $payload = [
            'beer_name' => $beer->name,
            'brewery' => $beer->brewery?->name,
            'style' => $beer->style ? implode(', ', $beer->style) : null,
            'abv' => $beer->abv,
            'ibu' => $beer->ibu,
            'rating' => $checkin->rating,
            'serving_type' => $checkin->serving_type,
            'catalog_beer_id' => $beer->catalog_beer_id,
            'checked_in_at' => $checkin->created_at->toIso8601String(),
        ];

        try {
            $response = Http::accept('application/json')
                ->timeout(10)
                ->post(rtrim($pubUrl, '/') . '/api/checkins', $payload);

            if (! $response->successful()) {
                Log::warning('Pub checkin failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::warning('Pub checkin error', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
