<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockTransaction;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        if (auth()->user()->hasRole('Kasir')) {
            $sales = StockTransaction::with('items.product')
                ->where('type', 'out')
                ->where('user_id', auth()->id());

            return view('dashboard-cashier', [
                'todaySales' => (clone $sales)->whereDate('transaction_date', today())->count(),
                'todayRevenue' => (clone $sales)->whereDate('transaction_date', today())->get()
                    ->sum(fn ($sale) => $sale->items->sum(fn ($item) => $item->qty * $item->price)),
                'recentSales' => $sales->latest('transaction_date')->latest('id')->take(8)->get(),
            ]);
        }

        $months = collect(range(11, 0))->map(fn ($n) => now()->startOfMonth()->subMonths($n));
        $monthStart = $months->first()->copy()->startOfMonth()->toDateString();
        $movement = DB::table('stock_transaction_items')
            ->join('stock_transactions', 'stock_transactions.id', '=', 'stock_transaction_items.stock_transaction_id')
            ->whereIn('stock_transactions.type', ['in', 'out'])
            ->where('stock_transactions.transaction_date', '>=', $monthStart)
            ->selectRaw('YEAR(stock_transactions.transaction_date) as year, MONTH(stock_transactions.transaction_date) as month, stock_transactions.type, SUM(stock_transaction_items.qty) as units')
            ->groupBy('year', 'month', 'stock_transactions.type')
            ->get()
            ->keyBy(fn ($row) => $row->year.'-'.$row->month.'-'.$row->type);
        $series = fn ($type) => $months->map(fn ($month) => (int) ($movement->get($month->year.'-'.$month->month.'-'.$type)?->units ?? 0))->values();
        $todayMovement = DB::table('stock_transaction_items')
            ->join('stock_transactions', 'stock_transactions.id', '=', 'stock_transaction_items.stock_transaction_id')
            ->whereDate('stock_transactions.transaction_date', today())
            ->whereIn('stock_transactions.type', ['in', 'out'])
            ->selectRaw('stock_transactions.type, SUM(stock_transaction_items.qty) as units')
            ->groupBy('stock_transactions.type')
            ->pluck('units', 'type');
        $todaySales = StockTransaction::with('items.product')
            ->where('type', 'out')->whereDate('transaction_date', today())->get();
        $todayRevenue = $todaySales->sum(fn ($sale) => $sale->items->sum(fn ($item) => $item->qty * $item->price));
        $todayProfit = $todaySales->sum(fn ($sale) => $sale->items->sum(function ($item) {
            $cost = $item->cost_price ?? $item->product?->cost_price ?? 0;
            return $item->qty * ($item->price - $cost);
        }));
        $lowQuery = Product::where('minimum_stock', '>', 0)->whereColumn('current_stock', '<=', 'minimum_stock');

        return view('dashboard', [
            'todayRevenue' => $todayRevenue,
            'todayProfit' => $todayProfit,
            'todayInboundUnits' => (int) ($todayMovement['in'] ?? 0),
            'todayOutboundUnits' => (int) ($todayMovement['out'] ?? 0),
            'months' => $months,
            'inSeries' => $series('in'),
            'outSeries' => $series('out'),
            'products' => Product::count(),
            'stockUnits' => (int) Product::sum('current_stock'),
            'value' => Product::selectRaw('coalesce(sum(current_stock * cost_price), 0) as total')->value('total') ?? 0,
            'low' => (clone $lowQuery)->orderBy('current_stock')->take(6)->get(),
            'lowCount' => $lowQuery->count(),
            'recent' => StockTransaction::with(['user', 'items'])->latest()->take(7)->get(),
        ]);
    }
}
