<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('beer_id')->constrained()->cascadeOnDelete();
            $table->decimal('rating', 3, 1)->nullable();
            $table->text('notes')->nullable();
            $table->string('serving_type')->nullable(); // draft, bottle, can, crowler, growler
            $table->string('location')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkins');
    }
};
