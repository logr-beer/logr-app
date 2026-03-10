<?php

use App\Models\Venue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Get all unique non-null location strings from checkins that don't already have a venue_id
        $locations = DB::table('checkins')
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->whereNull('venue_id')
            ->distinct()
            ->pluck('location');

        foreach ($locations as $locationName) {
            $venue = Venue::firstOrCreate(['name' => $locationName]);

            DB::table('checkins')
                ->where('location', $locationName)
                ->whereNull('venue_id')
                ->update(['venue_id' => $venue->id]);
        }
    }

    public function down(): void
    {
        // The location text column still has the original data, so just clear venue_id
        DB::table('checkins')
            ->whereNotNull('venue_id')
            ->update(['venue_id' => null]);
    }
};
