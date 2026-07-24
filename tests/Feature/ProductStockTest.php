<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockHistory;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_stock_is_saved_and_recorded_in_history(): void
    {
        $user = User::factory()->create();
        $warehouse = Warehouse::create(['name' => 'Gudang Uji']);

        $response = $this->actingAs($user)->post(route('products.store'), [
            'name' => 'Produk Uji',
            'barcode' => 'BAR-001',
            'code' => 'PRD-001',
            'sku' => 'SKU-001',
            'warehouse_id' => $warehouse->id,
            'unit' => 'Pcs',
            'cost_price' => 10000,
            'selling_price' => 12000,
            'minimum_stock' => 5,
            'current_stock' => 25,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('products.index'));
        $product = Product::where('sku', 'SKU-001')->firstOrFail();
        $this->assertSame(25, $product->current_stock);
        $this->assertDatabaseHas('stock_histories', [
            'product_id' => $product->id,
            'quantity_in' => 25,
            'quantity_out' => 0,
            'balance' => 25,
            'activity' => 'Stok awal produk',
        ]);
    }

    public function test_stock_adjustment_is_saved_and_recorded(): void
    {
        $user = User::factory()->create();
        $warehouse = Warehouse::create(['name' => 'Gudang Uji']);
        $product = Product::create([
            'name' => 'Produk Uji', 'barcode' => 'BAR-002', 'code' => 'PRD-002', 'sku' => 'SKU-002',
            'warehouse_id' => $warehouse->id, 'unit' => 'Pcs', 'cost_price' => 10000,
            'selling_price' => 12000, 'minimum_stock' => 5, 'current_stock' => 10, 'status' => 'active',
        ]);

        $response = $this->actingAs($user)->put(route('products.update', $product), [
            'name' => $product->name,
            'barcode' => $product->barcode,
            'code' => $product->code,
            'sku' => $product->sku,
            'warehouse_id' => $warehouse->id,
            'unit' => 'Pcs',
            'cost_price' => 10000,
            'selling_price' => 12000,
            'minimum_stock' => 5,
            'current_stock' => 18,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertSame(18, $product->fresh()->current_stock);
        $this->assertDatabaseHas('stock_histories', [
            'product_id' => $product->id,
            'quantity_in' => 8,
            'quantity_out' => 0,
            'balance' => 18,
            'activity' => 'Penyesuaian stok produk',
        ]);
    }

    public function test_history_page_can_display_an_archived_product(): void
    {
        $user = User::factory()->create();
        $warehouse = Warehouse::create(['name' => 'Gudang Uji']);
        $product = Product::create([
            'name' => 'Produk Arsip', 'barcode' => 'BAR-ARSIP', 'code' => 'PRD-ARSIP', 'sku' => 'SKU-ARSIP',
            'warehouse_id' => $warehouse->id, 'unit' => 'Pcs', 'cost_price' => 10000,
            'selling_price' => 12000, 'minimum_stock' => 1, 'current_stock' => 5, 'status' => 'active',
        ]);
        StockHistory::create([
            'product_id' => $product->id, 'user_id' => $user->id, 'occurred_at' => now(),
            'quantity_in' => 5, 'quantity_out' => 0, 'balance' => 5, 'activity' => 'Stok awal produk',
            'reference_type' => Product::class, 'reference_id' => $product->id,
        ]);
        $product->delete();

        $this->actingAs($user)->get(route('stock.history'))
            ->assertOk()
            ->assertSee('Produk Arsip')
            ->assertSee('Diarsipkan');
    }
}
