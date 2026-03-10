<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkin_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkin_id')->constrained()->cascadeOnDelete();
            $table->string('photo_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkin_photos');
    }
};
