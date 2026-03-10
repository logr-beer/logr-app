<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->boolean('is_dynamic')->default(false)->after('cover_path');
            $table->json('rules')->nullable()->after('is_dynamic');
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn(['is_dynamic', 'rules']);
        });
    }
};
