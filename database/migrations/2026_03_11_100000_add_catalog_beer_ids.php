<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beers', function (Blueprint $table) {
            $table->string('catalog_beer_id')->nullable()->unique()->after('data');
        });

        Schema::table('breweries', function (Blueprint $table) {
            $table->string('catalog_beer_brewer_id')->nullable()->unique()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('beers', function (Blueprint $table) {
            $table->dropColumn('catalog_beer_id');
        });

        Schema::table('breweries', function (Blueprint $table) {
            $table->dropColumn('catalog_beer_brewer_id');
        });
    }
};
