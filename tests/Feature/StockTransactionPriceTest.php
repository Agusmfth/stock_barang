<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Salesperson;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransactionPriceTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $warehouse = Warehouse::create(['name' => 'Gudang Uji']);

        return Product::create([
            'name' => 'Produk Uji', 'barcode' => 'BAR-TRX', 'code' => 'PRD-TRX', 'sku' => 'SKU-TRX',
            'warehouse_id' => $warehouse->id, 'unit' => 'Pcs', 'cost_price' => 15000,
            'selling_price' => 21000, 'minimum_stock' => 5, 'current_stock' => 10, 'status' => 'active',
        ]);
    }

    public function test_inbound_uses_cost_price_when_price_is_empty(): void
    {
        $product = $this->product();
        $supplier = Supplier::create(['name' => 'Supplier Uji']);

        $this->actingAs(User::factory()->create())->post(route('stock.inbound.store'), [
            'transaction_date' => today()->format('Y-m-d'),
            'product_id' => $product->id,
            'qty' => 3,
            'supplier_id' => $supplier->id,
        ])->assertRedirect(route('stock.inbound'));

        $this->assertDatabaseHas('stock_transaction_items', [
            'product_id' => $product->id,
            'qty' => 3,
            'price' => 15000,
        ]);
    }

    public function test_outbound_uses_selling_price_when_price_is_empty(): void
    {
        $product = $this->product();
        $salesperson = Salesperson::create(['name' => 'Sales Uji', 'status' => 'active']);

        $this->actingAs(User::factory()->create())->post(route('stock.outbound.store'), [
            'transaction_date' => today()->format('Y-m-d'),
            'product_id' => $product->id,
            'qty' => 2,
            'salesperson_id' => $salesperson->id,
            'destination' => 'Jakarta',
        ])->assertRedirect(route('stock.outbound'));

        $this->assertDatabaseHas('stock_transaction_items', [
            'product_id' => $product->id,
            'qty' => 2,
            'price' => 21000,
        ]);
    }
}
