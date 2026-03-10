<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('untappd_rss_feeds')->nullable()->after('untappd_rss_url');
        });

        // Migrate existing single URL into the new JSON array
        foreach (DB::table('users')->whereNotNull('untappd_rss_url')->where('untappd_rss_url', '!=', '')->get(['id', 'untappd_rss_url']) as $user) {
            DB::table('users')->where('id', $user->id)->update([
                'untappd_rss_feeds' => json_encode([
                    ['label' => 'Primary', 'url' => $user->untappd_rss_url],
                ]),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('untappd_rss_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('untappd_rss_url')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('untappd_rss_feeds');
        });
    }
};
