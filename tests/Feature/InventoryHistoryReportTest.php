<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryHistoryReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_inventory_history_report_is_available(): void
    {
        $warehouse = Warehouse::create(['name' => 'Gudang Uji']);
        Product::create([
            'name' => 'Produk Uji', 'barcode' => 'BAR-HISTORY', 'code' => 'PRD-HISTORY', 'sku' => 'SKU-HISTORY',
            'warehouse_id' => $warehouse->id, 'unit' => 'Pcs', 'cost_price' => 10000,
            'selling_price' => 12000, 'minimum_stock' => 1, 'current_stock' => 7, 'status' => 'active',
        ]);

        $response = $this->actingAs(User::factory()->create())->get(route('reports', 'inventory-history'));

        $response->assertOk()->assertSee('Riwayat Total Persediaan Harian')->assertSee('Rp 70.000');
    }
}
