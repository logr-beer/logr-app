<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkins', function (Blueprint $table) {
            $table->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('checkins', function (Blueprint $table) {
            $table->dropConstrainedForeignId('venue_id');
        });
    }
};
