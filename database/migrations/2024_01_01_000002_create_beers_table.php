<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('brewery_id')->nullable()->constrained()->nullOnDelete();
            $table->string('style')->nullable();
            $table->decimal('abv', 4, 1)->nullable();
            $table->decimal('ibu', 6, 1)->nullable();
            $table->text('description')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beers');
    }
};
