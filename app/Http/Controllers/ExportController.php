<?php

namespace App\Http\Controllers;

use App\Models\Beer;
use App\Models\Checkin;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function checkins(): StreamedResponse
    {
        $filename = 'logr-checkins-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'beer_name',
                'brewery_name',
                'brewery_city',
                'brewery_state',
                'brewery_country',
                'beer_style',
                'beer_abv',
                'beer_ibu',
                'rating_score',
                'serving_type',
                'comment',
                'venue_name',
                'venue_city',
                'venue_state',
                'venue_country',
                'created_at',
            ]);

            Checkin::where('user_id', auth()->id())
                ->with(['beer.brewery', 'venue'])
                ->latest()
                ->chunk(200, function ($checkins) use ($handle) {
                    foreach ($checkins as $checkin) {
                        fputcsv($handle, [
                            $checkin->beer->name ?? '',
                            $checkin->beer->brewery->name ?? '',
                            $checkin->beer->brewery->city ?? '',
                            $checkin->beer->brewery->state ?? '',
                            $checkin->beer->brewery->country ?? '',
                            $checkin->beer->style ? implode(', ', $checkin->beer->style) : '',
                            $checkin->beer->abv ?? '',
                            $checkin->beer->ibu ?? '',
                            $checkin->rating ?? '',
                            $checkin->serving_type ?? '',
                            $checkin->notes ?? '',
                            $checkin->venue->name ?? ($checkin->location ?? ''),
                            $checkin->venue->city ?? '',
                            $checkin->venue->state ?? '',
                            $checkin->venue->country ?? '',
                            $checkin->created_at->toIso8601String(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function beers(): StreamedResponse
    {
        $filename = 'logr-beers-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'beer_name',
                'brewery_name',
                'brewery_city',
                'brewery_state',
                'brewery_country',
                'beer_style',
                'beer_abv',
                'beer_ibu',
                'average_rating',
                'total_checkins',
                'is_favorite',
                'description',
            ]);

            Beer::with('brewery')
                ->withCount('checkins')
                ->orderBy('name')
                ->chunk(200, function ($beers) use ($handle) {
                    foreach ($beers as $beer) {
                        fputcsv($handle, [
                            $beer->name,
                            $beer->brewery->name ?? '',
                            $beer->brewery->city ?? '',
                            $beer->brewery->state ?? '',
                            $beer->brewery->country ?? '',
                            $beer->style ? implode(', ', $beer->style) : '',
                            $beer->abv ?? '',
                            $beer->ibu ?? '',
                            $beer->averageRating() ?: '',
                            $beer->checkins_count,
                            $beer->is_favorite ? 'yes' : 'no',
                            $beer->description ?? '',
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
