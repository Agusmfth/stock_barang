<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Expense;
use App\Models\SalesReturn;
use App\Models\StockTransaction;
use App\Support\OperationalReportExcel;
use App\Support\ProfitReportExcel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    public function profit(Request $request)
    {
        $data = $this->profitData($request);

        foreach ([
            'rows' => ['page_summary', 'per_page_summary'],
            'details' => ['page_details', 'per_page_details'],
        ] as $key => [$pageName, $perPageKey]) {
            $perPage = (int) $request->input($perPageKey, 10);
            abort_unless(in_array($perPage, [10, 25, 50, 100], true), 422);
            $items = collect($data[$key]);
            $page = LengthAwarePaginator::resolveCurrentPage($pageName);
            $data[$key] = new LengthAwarePaginator(
                $items->forPage($page, $perPage)->values(),
                $items->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query(), 'pageName' => $pageName]
            );
            $data[$key.'PerPage'] = $perPage;
        }

        return view('reports.profit', $data);
    }

    public function exportProfit(Request $request, string $format)
    {
        abort_unless(in_array($format, ['pdf', 'excel'], true), 404);
        $data = $this->profitData($request);
        $filename = 'laporan-keuntungan-'.$data['period'].'-'.$data['start']->format('Ymd').'-'.$data['end']->format('Ymd');

        if ($format === 'pdf') {
            return Pdf::loadView('reports.exports.profit-pdf', $data)
                ->setPaper('a4', 'landscape')
                ->download($filename.'.pdf');
        }

        return response((new ProfitReportExcel())->make($data), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.xlsx"',
        ]);
    }

    public function __invoke(Request $request, string $type = 'inventory')
    {
        $perPage = (int) $request->input('per_page', 10);
        abort_unless(in_array($perPage, [10, 25, 50, 100], true), 422);

        $data = $this->operationalData($request, $type);
        $rows = collect($data['rows']);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $data['rows'] = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        $data['perPage'] = $perPage;

        return view('reports.index', $data);
    }

    public function print(Request $request, string $type)
    {
        return view('reports.print', $this->operationalData($request, $type));
    }

    public function export(Request $request, string $type, string $format)
    {
        abort_unless(in_array($format, ['pdf', 'excel'], true), 404);
        $data = $this->operationalData($request, $type);
        $filename = 'laporan-operasional-'.$type.'-'.now()->format('Ymd-His');

        if ($format === 'pdf') {
            return Pdf::loadView('reports.exports.operational-pdf', $data)
                ->setPaper('a4', 'landscape')
                ->download($filename.'.pdf');
        }

        return response((new OperationalReportExcel())->make($data), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.xlsx"',
        ]);
    }

    private function operationalData(Request $request, string $type): array
    {
        abort_unless(in_array($type, ['inventory', 'inventory-history', 'inbound', 'outbound'], true), 404);

        if ($type === 'inventory') {
            $rows = Product::orderBy('name')->get();
            $units = (int) $rows->sum('current_stock');

            return [
                'type' => $type,
                'rows' => $rows,
                'period' => null,
                'periodLabel' => 'Posisi stok per '.today()->translatedFormat('d F Y'),
                'totalProducts' => $rows->count(),
                'totalUnits' => $units,
                'totalValue' => $rows->sum(fn ($product) => $product->current_stock * (float) $product->cost_price),
            ];
        }

        $range = $this->operationalRange($request);

        if ($type === 'inventory-history') {
            $rows = $this->dailyInventoryHistory($range['start'], $range['end']);

            return $range + [
                'type' => $type,
                'rows' => $rows,
                'totalInbound' => array_sum(array_column($rows, 'inbound')),
                'totalOutbound' => array_sum(array_column($rows, 'outbound')),
                'totalUnits' => $rows ? $rows[array_key_last($rows)]['units'] : 0,
                'totalValue' => $rows ? $rows[array_key_last($rows)]['value'] : 0,
            ];
        }

        $transactionType = $type === 'inbound' ? 'in' : 'out';
        $rows = StockTransaction::with(['items.product', 'user'])
            ->where('type', $transactionType)
            ->whereBetween('transaction_date', [$range['start']->toDateString(), $range['end']->toDateString()])
            ->latest('transaction_date')->latest('id')->get();
        $items = $rows->flatMap->items;

        return $range + [
            'type' => $type,
            'rows' => $rows,
            'totalTransactions' => $rows->count(),
            'totalUnits' => (int) $items->sum('qty'),
            'totalAmount' => $items->sum(fn ($item) => $item->qty * (float) $item->price),
        ];
    }

    private function operationalRange(Request $request): array
    {
        $filters = $request->validate([
            'period' => ['nullable', Rule::in(['daily', 'weekly', 'monthly'])],
            'date' => ['nullable', 'date'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $period = $filters['period'] ?? 'daily';
        $selectedMonth = $filters['month'] ?? (isset($filters['date']) ? Carbon::parse($filters['date'])->format('Y-m') : today()->format('Y-m'));

        if ($period === 'daily') {
            $anchor = Carbon::parse($filters['date'] ?? today()->toDateString());
            $start = $anchor->copy()->startOfDay();
            $end = $anchor->copy()->endOfDay();
        } elseif ($period === 'weekly') {
            $defaultStart = today()->copy()->startOfWeek(Carbon::MONDAY);
            if ($request->filled('date_from') || $request->filled('date_to')) {
                $dates = $request->validate([
                    'date_from' => ['required', 'date'],
                    'date_to' => ['required', 'date', 'after_or_equal:date_from'],
                ]);
                $selectedFrom = $dates['date_from'];
                $selectedTo = $dates['date_to'];
            } else {
                $selectedFrom = $defaultStart->toDateString();
                $selectedTo = $defaultStart->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();
            }
            $start = Carbon::parse($selectedFrom)->startOfDay();
            $end = Carbon::parse($selectedTo)->endOfDay();
            if ($start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) > 6) {
                throw ValidationException::withMessages(['date_to' => 'Rentang mingguan maksimal 7 hari.']);
            }
        } else {
            $start = Carbon::parse($selectedMonth.'-01')->startOfDay();
            $end = $start->copy()->endOfMonth()->endOfDay();
        }

        return [
            'period' => $period,
            'periodLabel' => match ($period) {
                'weekly' => $start->translatedFormat('d M Y').' – '.$end->translatedFormat('d M Y'),
                'monthly' => $start->translatedFormat('F Y'),
                default => $start->translatedFormat('d F Y'),
            },
            'start' => $start,
            'end' => $end,
            'selectedDate' => $request->input('date', today()->toDateString()),
            'selectedMonth' => $selectedMonth,
            'selectedFrom' => $selectedFrom ?? $request->input('date_from', today()->copy()->startOfWeek(Carbon::MONDAY)->toDateString()),
            'selectedTo' => $selectedTo ?? $request->input('date_to', today()->copy()->endOfWeek(Carbon::SUNDAY)->toDateString()),
        ];
    }

    private function dailyInventoryHistory(Carbon $start, Carbon $end): array
    {
        $products = Product::with(['histories' => fn ($query) => $query->orderBy('occurred_at')])->get();
        $rows = [];

        $date = $start->copy()->startOfDay();
        $lastDate = $end->copy()->startOfDay();
        while ($date->lte($lastDate)) {
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
            $date->addDay();
        }

        return $rows;
    }

    private function profitData(Request $request): array
    {
        $filters = $request->validate([
            'period' => ['nullable', Rule::in(['daily', 'weekly', 'monthly'])],
            'date' => ['nullable', 'date'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $period = $filters['period'] ?? 'daily';
        $fallbackMonth = isset($filters['date']) ? Carbon::parse($filters['date'])->format('Y-m') : today()->format('Y-m');
        $selectedMonth = $filters['month'] ?? $fallbackMonth;
        $monthStart = Carbon::parse($selectedMonth.'-01')->startOfDay();

        if ($period === 'daily') {
            $anchor = Carbon::parse($filters['date'] ?? today()->toDateString());
            $start = $anchor->copy()->startOfDay();
            $end = $anchor->copy()->endOfDay();
        } elseif ($period === 'weekly') {
            $defaultStart = today()->copy()->startOfWeek(Carbon::MONDAY);
            if ($request->filled('date_from') || $request->filled('date_to')) {
                $range = $request->validate([
                    'date_from' => ['required', 'date'],
                    'date_to' => ['required', 'date', 'after_or_equal:date_from'],
                ]);
                $selectedFrom = $range['date_from'];
                $selectedTo = $range['date_to'];
            } else {
                $selectedFrom = $defaultStart->toDateString();
                $selectedTo = $defaultStart->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();
            }
            $start = Carbon::parse($selectedFrom)->startOfDay();
            $end = Carbon::parse($selectedTo)->endOfDay();
            if ($start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) > 6) {
                throw ValidationException::withMessages(['date_to' => 'Rentang mingguan maksimal 7 hari.']);
            }
        } else {
            $start = $monthStart;
            $end = $monthStart->copy()->endOfMonth()->endOfDay();
        }

        $selectedFrom ??= $request->input('date_from', today()->copy()->startOfWeek(Carbon::MONDAY)->toDateString());
        $selectedTo ??= $request->input('date_to', today()->copy()->endOfWeek(Carbon::SUNDAY)->toDateString());

        $transactions = StockTransaction::with(['items.product', 'user'])
            ->where('type', 'out')
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();
        $returns = SalesReturn::with(['items.product', 'sale', 'user'])
            ->whereBetween('returned_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->orderBy('returned_at')
            ->orderBy('id')
            ->get();
        $expenses = Expense::with('user')
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('expense_date')
            ->orderBy('id')
            ->get();

        $details = [];
        $legacyCostItems = 0;
        foreach ($transactions as $transaction) {
            foreach ($transaction->items as $item) {
                $costPrice = $item->cost_price;
                if ($costPrice === null) {
                    $costPrice = $item->product?->cost_price ?? 0;
                    $legacyCostItems++;
                }
                $revenue = (float) $item->price * $item->qty;
                $cost = (float) $costPrice * $item->qty;
                $details[] = [
                    'kind' => 'Penjualan',
                    'date' => $transaction->transaction_date->copy(),
                    'reference' => $transaction->reference_no ?: 'PJ-'.str_pad((string) $transaction->id, 6, '0', STR_PAD_LEFT),
                    'cashier' => $transaction->user?->name ?? 'User dihapus',
                    'product' => $item->product?->name ?? 'Produk dihapus',
                    'qty' => $item->qty,
                    'event_id' => 'sale-'.$transaction->id,
                    'revenue' => $revenue,
                    'cost' => $cost,
                    'expense' => 0,
                    'profit' => $revenue - $cost,
                ];
            }
        }

        foreach ($returns as $salesReturn) {
            foreach ($salesReturn->items as $item) {
                $revenue = -1 * (float) $item->price * $item->qty;
                $cost = -1 * (float) $item->cost_price * $item->qty;
                $details[] = [
                    'kind' => 'Retur',
                    'date' => $salesReturn->returned_at->copy(),
                    'reference' => $salesReturn->return_no,
                    'cashier' => $salesReturn->user?->name ?? 'User dihapus',
                    'product' => $item->product?->name ?? 'Produk dihapus',
                    'qty' => $item->qty,
                    'event_id' => 'return-'.$salesReturn->id,
                    'revenue' => $revenue,
                    'cost' => $cost,
                    'expense' => 0,
                    'profit' => $revenue - $cost,
                ];
            }
        }

        foreach ($expenses as $expense) {
            $amount = (float) $expense->amount;
            $details[] = [
                'kind' => 'Pengeluaran',
                'date' => $expense->expense_date->copy()->startOfDay(),
                'reference' => 'BY-'.str_pad((string) $expense->id, 6, '0', STR_PAD_LEFT),
                'cashier' => $expense->user?->name ?? '—',
                'product' => $expense->description,
                'qty' => 1,
                'event_id' => 'expense-'.$expense->id,
                'revenue' => 0,
                'cost' => 0,
                'expense' => $amount,
                'profit' => -$amount,
            ];
        }
        usort($details, fn ($left, $right) => $left['date']->getTimestamp() <=> $right['date']->getTimestamp());

        $dailyGroups = collect($details)->groupBy(fn ($detail) => $detail['date']->toDateString());
        $rows = collect();
        $cursor = $start->copy()->startOfDay();
        $lastDay = $end->copy()->startOfDay();
        while ($cursor->lte($lastDay)) {
            $day = $cursor->toDateString();
            $dayDetails = $dailyGroups->get($day, collect());
            if ($dayDetails->isNotEmpty()) {
                $rows->push([
                    'date' => $cursor->copy(),
                    'transactions' => $dayDetails->where('kind', '!=', 'Pengeluaran')->pluck('event_id')->unique()->count(),
                    'revenue' => $dayDetails->sum('revenue'),
                    'cost' => $dayDetails->sum('cost'),
                    'profit' => $dayDetails->where('kind', '!=', 'Pengeluaran')->sum('profit'),
                    'expenses' => $dayDetails->sum('expense'),
                    'net_profit' => $dayDetails->sum('profit'),
                ]);
            }
            $cursor->addDay();
        }

        $totalRevenue = collect($details)->sum('revenue');
        $totalCost = collect($details)->sum('cost');
        $totalExpenses = collect($details)->sum('expense');

        return [
            'period' => $period,
            'periodLabel' => match ($period) {
                'weekly' => $start->translatedFormat('d M Y').' – '.$end->translatedFormat('d M Y'),
                'monthly' => $start->translatedFormat('F Y'),
                default => $start->translatedFormat('d F Y'),
            },
            'selectedMonth' => $selectedMonth,
            'selectedFrom' => $selectedFrom,
            'selectedTo' => $selectedTo,
            'start' => $start,
            'end' => $end,
            'rows' => $rows,
            'details' => collect($details),
            'totalTransactions' => $transactions->count() + $returns->count(),
            'totalRevenue' => $totalRevenue,
            'totalCost' => $totalCost,
            'totalProfit' => $totalRevenue - $totalCost,
            'totalExpenses' => $totalExpenses,
            'totalNetProfit' => $totalRevenue - $totalCost - $totalExpenses,
            'legacyCostItems' => $legacyCostItems,
        ];
    }
}
