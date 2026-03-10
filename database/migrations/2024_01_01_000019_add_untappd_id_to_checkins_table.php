<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkins', function (Blueprint $table) {
            $table->string('untappd_id')->nullable()->after('id');
            $table->index('untappd_id');
        });
    }

    public function down(): void
    {
        Schema::table('checkins', function (Blueprint $table) {
            $table->dropIndex(['untappd_id']);
            $table->dropColumn('untappd_id');
        });
    }
};
