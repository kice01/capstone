<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('status', 20)->default('completed')->after('total_amount');
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable();
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->change();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->restrictOnDelete();
            $table->string('reason', 255)->nullable();
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->foreign('supplier_id')->references('id')->on('suppliers')->restrictOnDelete();
            $table->string('source', 32)->default('manual');
        });

        Schema::create('sale_batch_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();
            $table->unique(['sale_item_id', 'batch_id']);
        });

        Schema::create('inventory_batch_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();
            $table->unique(['inventory_id', 'batch_id']);
        });

        Schema::create('daily_closings', function (Blueprint $table) {
            $table->id();
            $table->date('close_date')->unique();
            $table->foreignId('closed_by')->constrained('users')->restrictOnDelete();
            $table->decimal('expected_cash', 12, 2);
            $table->decimal('expected_gcash', 12, 2);
            $table->decimal('actual_cash', 12, 2);
            $table->decimal('actual_gcash', 12, 2);
            $table->string('notes', 1000)->nullable();
            $table->timestamp('closed_at');
            $table->timestamps();
        });

        // The product's current quantity is the best available authority for legacy stock.
        // Adjust existing active batches to that total, then create an opening batch for any
        // quantity that had never been assigned to a batch.
        DB::transaction(function () {
            DB::table('products')->orderBy('id')->chunkById(200, function ($products) {
                foreach ($products as $product) {
                    $target = max(0, (int) $product->quantity);
                    $batches = DB::table('batches')
                        ->where('product_id', $product->id)
                        ->where('status', 'active')
                        ->where('quantity', '>', 0)
                        ->orderByRaw('expiration_date IS NULL, expiration_date ASC')
                        ->orderBy('id')
                        ->get();

                    $batchTotal = (int) $batches->sum('quantity');

                    if ($batchTotal > $target) {
                        $excess = $batchTotal - $target;
                        foreach ($batches as $batch) {
                            if ($excess <= 0) {
                                break;
                            }

                            $take = min((int) $batch->quantity, $excess);
                            $left = (int) $batch->quantity - $take;
                            DB::table('batches')->where('id', $batch->id)->update([
                                'quantity' => $left,
                                'status' => $left > 0 ? 'active' : 'depleted',
                            ]);
                            $excess -= $take;
                        }
                    } elseif ($batchTotal < $target) {
                        DB::table('batches')->insert([
                            'product_id' => $product->id,
                            'supplier_id' => null,
                            'quantity' => $target - $batchTotal,
                            'expiration_date' => $product->expiry_date,
                            'date_received' => substr($product->created_at ?? now()->toDateString(), 0, 10),
                            'status' => 'active',
                            'source' => 'opening_balance',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    $earliest = DB::table('batches')
                        ->where('product_id', $product->id)
                        ->where('status', 'active')
                        ->where('quantity', '>', 0)
                        ->orderByRaw('expiration_date IS NULL, expiration_date ASC')
                        ->orderBy('id')
                        ->value('expiration_date');
                    DB::table('products')->where('id', $product->id)->update([
                        'quantity' => $target,
                        'expiry_date' => $earliest,
                    ]);
                }
            });
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_closings');
        Schema::dropIfExists('inventory_batch_allocations');
        Schema::dropIfExists('sale_batch_allocations');

        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn('source');
            $table->dropForeign(['supplier_id']);
            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('user_id');
            $table->dropColumn('reason');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['voided_by']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'status', 'voided_at', 'voided_by', 'void_reason']);
        });
    }
};
