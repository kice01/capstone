<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('open_food_facts_cache', function (Blueprint $table) {
            $table->string('barcode', 255)->primary();
            $table->string('product_name')->nullable();
            $table->json('categories')->nullable();
            $table->boolean('found')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('open_food_facts_cache');
    }
};
