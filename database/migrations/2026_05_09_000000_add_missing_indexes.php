<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkins', function (Blueprint $table) {
            $table->index('beer_id');
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('beer_id');
        });
    }

    public function down(): void
    {
        Schema::table('checkins', function (Blueprint $table) {
            $table->dropIndex(['beer_id']);
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['beer_id']);
        });
    }
};
