<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('untappd_client_id')->nullable()->after('catalog_beer_api_key');
            $table->text('untappd_client_secret')->nullable()->after('untappd_client_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['untappd_client_id', 'untappd_client_secret']);
        });
    }
};
