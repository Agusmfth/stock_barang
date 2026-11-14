<?php

namespace App\Http\Controllers;

use App\Models\{Product, StockTransaction, StockHistory};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockTransactionController extends Controller
{
    private function data(): array
    {
        return ['products' => Product::where('status', 'active')->orderBy('name')->get()];
    }

    public function inbound() { return view('stock.form', $this->data() + ['type' => 'in', 'isCashier' => false]); }
    public function outbound()
    {
        $data = $this->data();

        return request()->user()->hasRole('Kasir')
            ? view('stock.cashier', $data)
            : view('stock.form', $data + ['type' => 'out', 'isCashier' => false]);
    }

    private function save(Request $request, string $type)
    {
        if ($type === 'out' && $request->user()->hasRole('Kasir')) {
            return $this->saveCashierCart($request);
        }

        if ($request->has('price')) {
            $value = str_replace('.', '', (string) $request->input('price'));
            $request->merge(['price' => str_replace(',', '.', $value)]);
        }

        $data = $request->validate(['transaction_date' => 'required|date', 'product_id' => 'required|exists:products,id', 'qty' => 'required|integer|min:1', 'price' => 'nullable|numeric|min:0', 'reference_no' => 'nullable|max:100', 'destination' => 'nullable|max:150', 'notes' => 'nullable|max:2000']);
        $isCashier = $request->user()->hasRole('Kasir');
        if ($type === 'out' && $isCashier) $request->validate(['destination' => 'nullable|max:150']);
        elseif ($type === 'out') $request->validate(['destination' => 'required|max:150']);

        $data['supplier_id'] = null;
        $data['salesperson_id'] = null;

        DB::transaction(function () use ($data, $type, $request): void {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);
            if ($type === 'out' && $product->current_stock < $data['qty']) throw ValidationException::withMessages(['qty' => "Stok {$product->name} hanya tersedia {$product->current_stock}."]);
            $transaction = StockTransaction::create($data + ['type' => $type, 'user_id' => $request->user()->id]);
            $price = $type === 'out' && $request->user()->hasRole('Kasir')
                ? $product->selling_price
                : ($data['price'] ?? ($type === 'in' ? $product->cost_price : $product->selling_price));
            $transaction->items()->create([
                'product_id' => $product->id,
                'qty' => $data['qty'],
                'price' => $price,
                'cost_price' => $type === 'out' ? $product->cost_price : null,
            ]);
            $product->current_stock += $type === 'in' ? $data['qty'] : -$data['qty'];
            $product->save();
            StockHistory::create(['product_id' => $product->id, 'user_id' => $request->user()->id, 'occurred_at' => now(), 'quantity_in' => $type === 'in' ? $data['qty'] : 0, 'quantity_out' => $type === 'out' ? $data['qty'] : 0, 'balance' => $product->current_stock, 'activity' => $type === 'in' ? 'Barang masuk' : 'Barang keluar', 'reference_type' => StockTransaction::class, 'reference_id' => $transaction->id]);
        });

        $destination = $type === 'out' && $isCashier ? 'sales.history' : 'stock.'.($type === 'in' ? 'inbound' : 'outbound');
        return redirect()->route($destination)->with('success', $type === 'out' ? 'Penjualan berhasil dicatat dan stok telah diperbarui.' : 'Transaksi berhasil, stok telah diperbarui.');
    }

    private function saveCashierCart(Request $request)
    {
        $data = $request->validate([
            'transaction_date' => ['required', 'date'],
            'destination' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($data, $request): void {
            $productIds = collect($data['items'])->pluck('product_id')->map(fn ($id) => (int) $id)->sort()->values();
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($data['items'] as $line) {
                $product = $products->get((int) $line['product_id']);
                if (!$product || $product->status !== 'active') {
                    throw ValidationException::withMessages(['items' => 'Salah satu produk sudah tidak aktif. Muat ulang daftar barang dan coba lagi.']);
                }
                if ($product->current_stock < (int) $line['qty']) {
                    throw ValidationException::withMessages(['items' => "Stok {$product->name} hanya tersedia {$product->current_stock} {$product->unit}."]);
                }
            }

            $transaction = StockTransaction::create([
                'transaction_date' => $data['transaction_date'],
                'type' => 'out',
                'destination' => $data['destination'] ?? null,
                'notes' => $data['notes'] ?? null,
                'user_id' => $request->user()->id,
            ]);

            foreach ($data['items'] as $line) {
                $product = $products->get((int) $line['product_id']);
                $qty = (int) $line['qty'];

                $transaction->items()->create([
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'price' => $product->selling_price,
                    'cost_price' => $product->cost_price,
                ]);

                $product->current_stock -= $qty;
                $product->save();

                StockHistory::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'occurred_at' => now(),
                    'quantity_in' => 0,
                    'quantity_out' => $qty,
                    'balance' => $product->current_stock,
                    'activity' => 'Penjualan',
                    'reference_type' => StockTransaction::class,
                    'reference_id' => $transaction->id,
                ]);
            }
        });

        return redirect()->route('sales.history')->with('success', 'Penjualan berhasil dicatat. Stok seluruh barang di keranjang telah diperbarui.');
    }

    public function storeInbound(Request $request) { return $this->save($request, 'in'); }
    public function storeOutbound(Request $request) { return $this->save($request, 'out'); }
    public function history(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        abort_unless(in_array($perPage, [10, 25, 50, 100], true), 422);

        return view('stock.history', ['rows' => StockHistory::with(['product', 'user'])->latest('occurred_at')->paginate($perPage)->withQueryString(), 'perPage' => $perPage]);
    }

    public function salesHistory(Request $request)
    {
        $query = StockTransaction::with(['items.product', 'items.salesReturnItems', 'user'])
            ->where('type', 'out')
            ->latest('transaction_date')
            ->latest('id');

        if ($request->user()->hasRole('Kasir')) {
            $query->where('user_id', $request->user()->id);
        }

        $perPage = (int) $request->input('per_page', 10);
        abort_unless(in_array($perPage, [10, 25, 50, 100], true), 422);

        return view('stock.sales-history', ['sales' => $query->paginate($perPage)->withQueryString(), 'perPage' => $perPage]);
    }
}
