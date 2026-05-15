<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beers', function (Blueprint $table) {
            $table->string('pub_uuid')->nullable()->unique()->after('catalog_beer_id');
        });

        Schema::table('breweries', function (Blueprint $table) {
            $table->string('pub_uuid')->nullable()->unique()->after('catalog_beer_brewer_id');
        });
    }

    public function down(): void
    {
        Schema::table('beers', function (Blueprint $table) {
            $table->dropColumn('pub_uuid');
        });

        Schema::table('breweries', function (Blueprint $table) {
            $table->dropColumn('pub_uuid');
        });
    }
};
