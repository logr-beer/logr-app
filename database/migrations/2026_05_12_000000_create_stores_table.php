<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();

            $table->index('name');
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        // Migrate existing purchase_location values to stores
        $locations = DB::table('inventory')
            ->whereNotNull('purchase_location')
            ->where('purchase_location', '!=', '')
            ->distinct()
            ->pluck('purchase_location');

        foreach ($locations as $location) {
            $storeId = DB::table('stores')->insertGetId([
                'name' => $location,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('inventory')
                ->where('purchase_location', $location)
                ->update(['store_id' => $storeId]);
        }

        Schema::table('inventory', function (Blueprint $table) {
            $table->dropColumn('purchase_location');
        });
    }

    public function down(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->string('purchase_location')->nullable()->after('storage_location');
        });

        // Migrate store names back to purchase_location
        $inventories = DB::table('inventory')
            ->whereNotNull('store_id')
            ->get(['id', 'store_id']);

        foreach ($inventories as $inventory) {
            $store = DB::table('stores')->find($inventory->store_id);
            if ($store) {
                DB::table('inventory')
                    ->where('id', $inventory->id)
                    ->update(['purchase_location' => $store->name]);
            }
        }

        Schema::table('inventory', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
        });

        Schema::dropIfExists('stores');
    }
};
