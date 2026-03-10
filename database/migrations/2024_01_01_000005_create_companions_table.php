<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('checkin_companion');
        Schema::dropIfExists('companions');
    }
};
