<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkins', function (Blueprint $table) {
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('beers', function (Blueprint $table) {
            $table->index('name');
        });

        Schema::table('venues', function (Blueprint $table) {
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::table('checkins', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
        });

        Schema::table('beers', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });

        Schema::table('venues', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });
    }
};
