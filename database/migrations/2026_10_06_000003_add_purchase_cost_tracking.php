<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->decimal('unit_cost', 10, 2)->nullable();
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->decimal('unit_cost', 10, 2)->nullable();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
