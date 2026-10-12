<?php

use App\Models\Batch;
use App\Models\Category;
use App\Models\DailyClosing;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;

function createStockedProduct(int $quantity = 10, string $price = '12.00', ?string $unitCost = null): Product
{
    $category = Category::create(['name' => 'Test Category']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Test Product',
        'quantity' => $quantity,
        'unit_cost' => $unitCost,
        'price' => $price,
    ]);
    Batch::create([
        'product_id' => $product->id,
        'quantity' => $quantity,
        'date_received' => now()->toDateString(),
        'status' => 'active',
        'source' => 'test',
    ]);

    return $product;
}

function activeOwner(): User
{
    return User::factory()->create(['role' => 'owner', 'is_active' => true]);
}

it('deducts a cash sale from batches and records the cashier and inventory movement', function () {
    $owner = activeOwner();
    $product = createStockedProduct();

    $this->actingAs($owner)->post(route('sales.store'), [
        'sale_date' => now()->toDateString(),
        'payment_method' => 'Cash',
        'cash_tendered' => '30.00',
        'product_id' => [$product->id],
        'quantity' => [2],
    ])->assertRedirect();

    $sale = Sale::with('items.batchAllocations')->firstOrFail();
    expect($sale->user_id)->toBe($owner->id)
        ->and((float) $sale->total_amount)->toBe(24.0)
        ->and((float) $sale->change_amount)->toBe(6.0)
        ->and($sale->items->first()->batchAllocations->sum('quantity'))->toBe(2)
        ->and((int) $product->fresh()->quantity)->toBe(8)
        ->and((int) Batch::where('product_id', $product->id)->sum('quantity'))->toBe(8)
        ->and((int) Inventory::where('stock_out', 2)->count())->toBe(1);
});

it('records GCash as the exact sale total without requiring cash tendered', function () {
    $this->actingAs(activeOwner());
    $product = createStockedProduct();

    $this->post(route('sales.store'), [
        'sale_date' => now()->toDateString(),
        'payment_method' => 'GCash',
        'product_id' => [$product->id],
        'quantity' => [1],
    ])->assertRedirect();

    $sale = Sale::firstOrFail();
    expect($sale->payment_method)->toBe('GCash')
        ->and((float) $sale->cash_tendered)->toBe(12.0)
        ->and((float) $sale->change_amount)->toBe(0.0);
});

it('records purchase costs through receiving and carries batch cost into sale margin reports', function () {
    $owner = activeOwner();
    $this->actingAs($owner);
    $product = createStockedProduct(0);
    $supplier = Supplier::create(['name' => 'Test Supplier']);

    $this->post(route('inventories.store'), [
        'product_id' => $product->id,
        'supplier_id' => $supplier->id,
        'stock_in' => 5,
        'stock_out' => 0,
        'unit_cost' => '7.00',
        'expiry_date' => now()->addYear()->toDateString(),
        'reason' => 'Test delivery',
    ])->assertRedirect();

    $this->post(route('sales.store'), [
        'sale_date' => now()->toDateString(),
        'payment_method' => 'Cash',
        'cash_tendered' => '12.00',
        'product_id' => [$product->id],
        'quantity' => [1],
    ])->assertRedirect();

    expect((float) $product->fresh()->quantity)->toBe(4.0)
        ->and((float) $product->batches()->where('quantity', '>', 0)->first()->unit_cost)->toBe(7.0)
        ->and((float) $product->saleItems()->first()->unit_cost)->toBe(7.0);

    $this->get(route('reports.inventory'))->assertOk()->assertSee('Stock Value at Purchase Cost');
    $this->get(route('reports.sales'))->assertOk()->assertSee('Gross Profit');
});

it('voids a completed sale with a reason and restores its original batch stock', function () {
    $owner = activeOwner();
    $product = createStockedProduct();
    $this->actingAs($owner)->post(route('sales.store'), [
        'sale_date' => now()->toDateString(),
        'payment_method' => 'Cash',
        'cash_tendered' => '24.00',
        'product_id' => [$product->id],
        'quantity' => [2],
    ]);
    $sale = Sale::firstOrFail();

    $this->patch(route('sales.void', $sale), ['void_reason' => 'Duplicate transaction'])
        ->assertRedirect(route('sales.show', $sale));

    expect($sale->fresh()->status)->toBe('voided')
        ->and($sale->fresh()->voided_by)->toBe($owner->id)
        ->and((int) $product->fresh()->quantity)->toBe(10)
        ->and((int) Batch::where('product_id', $product->id)->sum('quantity'))->toBe(10)
        ->and((int) Inventory::where('stock_in', 2)->count())->toBe(1);
});

it('closes the day with a frozen cash and GCash reconciliation and blocks later sales', function () {
    $owner = activeOwner();
    $this->actingAs($owner);
    $product = createStockedProduct();
    $this->post(route('sales.store'), [
        'sale_date' => now()->toDateString(),
        'payment_method' => 'Cash',
        'cash_tendered' => '24.00',
        'product_id' => [$product->id],
        'quantity' => [2],
    ]);

    $this->post(route('reports.daily-close.store'), [
        'close_date' => now()->toDateString(),
        'opening_float' => '50.00',
        'actual_cash' => '74.00',
        'actual_gcash' => '0.00',
    ])->assertRedirect();

    $closing = DailyClosing::firstOrFail();
    expect((float) $closing->expected_cash)->toBe(24.0)
        ->and((float) $closing->opening_float)->toBe(50.0)
        ->and((float) $closing->expected_gcash)->toBe(0.0)
        ->and($closing->closed_by)->toBe($owner->id);

    $this->post(route('sales.store'), [
        'sale_date' => now()->toDateString(),
        'payment_method' => 'Cash',
        'cash_tendered' => '12.00',
        'product_id' => [$product->id],
        'quantity' => [1],
    ])->assertSessionHasErrors('sale_date');

    expect(Sale::count())->toBe(1);
});
