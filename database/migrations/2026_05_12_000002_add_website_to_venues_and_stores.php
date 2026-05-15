<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->string('website')->nullable()->after('country');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->string('website')->nullable()->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn('website');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('website');
        });
    }
};
