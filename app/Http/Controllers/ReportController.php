<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockTransaction;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __invoke(Request $request, string $type = 'inventory')
    {
        if ($type === 'inventory-history') {
            return view('reports.index', ['rows' => $this->dailyInventoryHistory(), 'type' => $type]);
        }

        $rows = $type === 'inventory'
            ? Product::with(['supplier', 'warehouse'])->get()
            : StockTransaction::with(['supplier', 'salesperson', 'items.product'])
                ->where('type', $type === 'inbound' ? 'in' : 'out')->latest('transaction_date')->get();

        return view('reports.index', compact('rows', 'type'));
    }

    private function dailyInventoryHistory(): array
    {
        $products = Product::with(['histories' => fn ($query) => $query->orderBy('occurred_at')])->get();
        $today = now()->startOfDay();
        $rows = [];

        foreach (range(29, 0) as $daysAgo) {
            $date = $today->copy()->subDays($daysAgo);
            $endOfDay = $date->copy()->endOfDay();
            $units = $value = $inbound = $outbound = 0;

            foreach ($products as $product) {
                $futureNet = $product->histories->filter(fn ($history) => $history->occurred_at->gt($endOfDay))->sum(fn ($history) => $history->quantity_in - $history->quantity_out);
                $stockAtDay = max(0, (int) $product->current_stock - (int) $futureNet);
                $units += $stockAtDay;
                $value += $stockAtDay * (float) $product->cost_price;
                $inbound += $product->histories->filter(fn ($history) => $history->occurred_at->isSameDay($date))->sum('quantity_in');
                $outbound += $product->histories->filter(fn ($history) => $history->occurred_at->isSameDay($date))->sum('quantity_out');
            }

            $rows[] = compact('date', 'units', 'value', 'inbound', 'outbound');
        }

        return $rows;
    }
}
