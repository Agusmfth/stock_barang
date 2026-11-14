<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\SalesReturn;
use App\Models\StockTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        abort_unless(in_array($perPage, [10, 25, 50, 100], true), 422);

        return view('expenses.index', [
            'expenses' => Expense::with('user')->latest('expense_date')->latest('id')->paginate($perPage)->withQueryString(),
            'perPage' => $perPage,
        ]);
    }

    public function profitPreview(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'exclude_expense' => ['nullable', 'integer'],
        ]);
        $date = Carbon::parse($data['date'])->toDateString();

        $sales = StockTransaction::with('items.product')
            ->where('type', 'out')->whereDate('transaction_date', $date)->get();
        $grossProfit = $sales->sum(fn ($sale) => $sale->items->sum(function ($item) {
            $cost = $item->cost_price ?? $item->product?->cost_price ?? 0;
            return $item->qty * ((float) $item->price - (float) $cost);
        }));

        $returns = SalesReturn::with('items')->whereDate('returned_at', $date)->get();
        $grossProfit -= $returns->sum(fn ($return) => $return->items->sum(
            fn ($item) => $item->qty * ((float) $item->price - (float) $item->cost_price)
        ));

        $otherExpenses = Expense::whereDate('expense_date', $date)
            ->when(isset($data['exclude_expense']), fn ($query) => $query->whereKeyNot($data['exclude_expense']))
            ->sum('amount');

        return response()->json([
            'gross_profit' => (float) $grossProfit,
            'other_expenses' => (float) $otherExpenses,
        ]);
    }

    public function create()
    {
        return view('expenses.form', ['expense' => new Expense(['expense_date' => today()])]);
    }

    public function store(Request $request)
    {
        Expense::create($this->validated($request) + ['category' => 'Pengeluaran', 'user_id' => $request->user()->id]);

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran berhasil dicatat.');
    }

    public function edit(Expense $expense)
    {
        return view('expenses.form', ['expense' => $expense]);
    }

    public function update(Request $request, Expense $expense)
    {
        $expense->update($this->validated($request));

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran berhasil diperbarui.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Catatan pengeluaran berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        if ($request->has('amount')) {
            $request->merge(['amount' => preg_replace('/\D+/', '', (string) $request->input('amount'))]);
        }

        $data = $request->validate([
            'expense_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:180'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999999'],
        ]);

        return $data;
    }
}
