<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SalesReturn;
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

            $todayReturns = SalesReturn::with('items')->where('user_id', auth()->id())->whereDate('returned_at', today())->get();
            $todaySalesAmount = (clone $sales)->whereDate('transaction_date', today())->get()
                ->sum(fn ($sale) => $sale->items->sum(fn ($item) => $item->qty * $item->price));

            return view('dashboard-cashier', [
                'todaySales' => (clone $sales)->whereDate('transaction_date', today())->count(),
                'todayRevenue' => $todaySalesAmount - $todayReturns->sum('refund_total'),
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
        $returnMovement = DB::table('sales_return_items')
            ->join('sales_returns', 'sales_returns.id', '=', 'sales_return_items.sales_return_id')
            ->where('sales_returns.returned_at', '>=', $monthStart)
            ->selectRaw('YEAR(sales_returns.returned_at) as year, MONTH(sales_returns.returned_at) as month, SUM(sales_return_items.qty) as units')
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(fn ($row) => $row->year.'-'.$row->month);
        $series = fn ($type) => $months->map(function ($month) use ($movement, $returnMovement, $type) {
            $units = (int) ($movement->get($month->year.'-'.$month->month.'-'.$type)?->units ?? 0);
            if ($type === 'in') {
                $units += (int) ($returnMovement->get($month->year.'-'.$month->month)?->units ?? 0);
            }
            return $units;
        })->values();
        $todayMovement = DB::table('stock_transaction_items')
            ->join('stock_transactions', 'stock_transactions.id', '=', 'stock_transaction_items.stock_transaction_id')
            ->whereDate('stock_transactions.transaction_date', today())
            ->whereIn('stock_transactions.type', ['in', 'out'])
            ->selectRaw('stock_transactions.type, SUM(stock_transaction_items.qty) as units')
            ->groupBy('stock_transactions.type')
            ->pluck('units', 'type');
        $todayReturnedUnits = DB::table('sales_return_items')
            ->join('sales_returns', 'sales_returns.id', '=', 'sales_return_items.sales_return_id')
            ->whereDate('sales_returns.returned_at', today())
            ->sum('sales_return_items.qty');
        $todaySales = StockTransaction::with('items.product')
            ->where('type', 'out')->whereDate('transaction_date', today())->get();
        $todayRevenue = $todaySales->sum(fn ($sale) => $sale->items->sum(fn ($item) => $item->qty * $item->price));
        $todayProfit = $todaySales->sum(fn ($sale) => $sale->items->sum(function ($item) {
            $cost = $item->cost_price ?? $item->product?->cost_price ?? 0;
            return $item->qty * ($item->price - $cost);
        }));
        $todayReturns = SalesReturn::with('items')->whereDate('returned_at', today())->get();
        $todayRevenue -= $todayReturns->sum('refund_total');
        $todayProfit -= $todayReturns->sum(fn ($return) => $return->items->sum(fn ($item) => $item->qty * ((float) $item->price - (float) $item->cost_price)));
        $lowQuery = Product::where('minimum_stock', '>', 0)->whereColumn('current_stock', '<=', 'minimum_stock');

        return view('dashboard', [
            'todayRevenue' => $todayRevenue,
            'todayProfit' => $todayProfit,
            'todayInboundUnits' => (int) ($todayMovement['in'] ?? 0) + (int) $todayReturnedUnits,
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
