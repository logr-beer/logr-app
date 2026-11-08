<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beer_user_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('beer_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'beer_id']);
        });

        // Favorites used to be a single global flag; hand them to the admin
        // (or the first user when there is no admin).
        $ownerId = DB::table('users')->where('is_admin', true)->orderBy('id')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');

        if ($ownerId) {
            $now = now();
            DB::table('beers')->where('is_favorite', true)->orderBy('id')->pluck('id')
                ->chunk(500)
                ->each(fn ($ids) => DB::table('beer_user_favorites')->insert(
                    $ids->map(fn ($beerId) => [
                        'user_id' => $ownerId,
                        'beer_id' => $beerId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                ));
        }

        Schema::table('beers', function (Blueprint $table) {
            $table->dropColumn('is_favorite');
        });
    }

    public function down(): void
    {
        Schema::table('beers', function (Blueprint $table) {
            $table->boolean('is_favorite')->default(false);
        });

        DB::table('beers')
            ->whereIn('id', DB::table('beer_user_favorites')->select('beer_id'))
            ->update(['is_favorite' => true]);

        Schema::dropIfExists('beer_user_favorites');
    }
};
