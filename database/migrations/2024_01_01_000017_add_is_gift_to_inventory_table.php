<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->boolean('is_gift')->default(false)->after('purchase_location');
        });
    }

    public function down(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->dropColumn('is_gift');
        });
    }
};
