<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breweries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('catalog_beer_brewer_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('beers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('brewery_id')->nullable()->constrained()->nullOnDelete();
            $table->json('style')->nullable();
            $table->decimal('abv', 4, 1)->nullable();
            $table->decimal('ibu', 6, 1)->nullable();
            $table->integer('release_year')->nullable();
            $table->string('brewer_master')->nullable();
            $table->text('description')->nullable();
            $table->json('data')->nullable();
            $table->string('catalog_beer_id')->nullable()->unique();
            $table->string('photo_path')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->timestamps();

            $table->index('name');
        });

        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('untappd_venue_id')->nullable()->unique();
            $table->timestamps();

            $table->index('name');
        });

        Schema::create('checkins', function (Blueprint $table) {
            $table->id();
            $table->string('untappd_id')->nullable()->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('beer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('rating', 4, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('serving_type')->nullable();
            $table->string('location')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('checkin_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkin_id')->constrained()->cascadeOnDelete();
            $table->string('photo_path');
            $table->timestamps();
        });

        Schema::create('companions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('avatar_path')->nullable();
            $table->timestamps();
        });

        Schema::create('checkin_companion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkin_id')->constrained()->cascadeOnDelete();
            $table->foreignId('companion_id')->constrained()->cascadeOnDelete();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color')->nullable();
            $table->timestamps();
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->morphs('taggable');
        });

        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->string('storage_location')->nullable();
            $table->string('purchase_location')->nullable();
            $table->boolean('is_gift')->default(false);
            $table->date('date_acquired')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('cover_path')->nullable();
            $table->boolean('is_dynamic')->default(false);
            $table->json('rules')->nullable();
            $table->timestamps();
        });

        Schema::create('beer_collection', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beer_collection');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('inventory');
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('checkin_companion');
        Schema::dropIfExists('companions');
        Schema::dropIfExists('checkin_photos');
        Schema::dropIfExists('checkins');
        Schema::dropIfExists('venues');
        Schema::dropIfExists('beers');
        Schema::dropIfExists('breweries');
    }
};
