<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('cover_path')->nullable();
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
    }
};
