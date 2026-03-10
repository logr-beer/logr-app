<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Convert existing single style strings to JSON arrays
        $beers = DB::table('beers')->whereNotNull('style')->get();

        foreach ($beers as $beer) {
            // Skip if already JSON
            if (str_starts_with($beer->style, '[')) {
                continue;
            }

            DB::table('beers')
                ->where('id', $beer->id)
                ->update(['style' => json_encode([$beer->style])]);
        }
    }

    public function down(): void
    {
        // Convert JSON arrays back to single strings
        $beers = DB::table('beers')->whereNotNull('style')->get();

        foreach ($beers as $beer) {
            if (! str_starts_with($beer->style, '[')) {
                continue;
            }

            $styles = json_decode($beer->style, true);
            DB::table('beers')
                ->where('id', $beer->id)
                ->update(['style' => $styles[0] ?? null]);
        }
    }
};
