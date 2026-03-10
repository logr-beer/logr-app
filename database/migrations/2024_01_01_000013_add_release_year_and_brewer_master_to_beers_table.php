<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beers', function (Blueprint $table) {
            $table->integer('release_year')->nullable()->after('ibu');
            $table->string('brewer_master')->nullable()->after('release_year');
        });
    }

    public function down(): void
    {
        Schema::table('beers', function (Blueprint $table) {
            $table->dropColumn(['release_year', 'brewer_master']);
        });
    }
};
