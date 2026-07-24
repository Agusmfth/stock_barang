<?php

namespace App\Http\Controllers;

use App\Models\{Product, Supplier, Salesperson, StockTransaction, StockHistory};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockTransactionController extends Controller
{
    private function data(): array
    {
        return ['products' => Product::where('status', 'active')->orderBy('name')->get(), 'suppliers' => Supplier::orderBy('name')->get(), 'salespeople' => Salesperson::where('status', 'active')->orderBy('name')->get()];
    }

    public function inbound() { return view('stock.form', $this->data() + ['type' => 'in']); }
    public function outbound() { return view('stock.form', $this->data() + ['type' => 'out']); }

    private function save(Request $request, string $type)
    {
        if ($request->has('price')) {
            $value = str_replace('.', '', (string) $request->input('price'));
            $request->merge(['price' => str_replace(',', '.', $value)]);
        }

        $data = $request->validate(['transaction_date' => 'required|date', 'product_id' => 'required|exists:products,id', 'qty' => 'required|integer|min:1', 'price' => 'nullable|numeric|min:0', 'supplier_id' => 'nullable|exists:suppliers,id', 'salesperson_id' => 'nullable|exists:salespeople,id', 'reference_no' => 'nullable|max:100', 'destination' => 'nullable|max:150', 'notes' => 'nullable|max:2000']);
        if ($type === 'in') $request->validate(['supplier_id' => 'required']);
        else $request->validate(['salesperson_id' => 'required', 'destination' => 'required|max:150']);

        DB::transaction(function () use ($data, $type, $request): void {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);
            if ($type === 'out' && $product->current_stock < $data['qty']) throw ValidationException::withMessages(['qty' => "Stok {$product->name} hanya tersedia {$product->current_stock}."]);
            $transaction = StockTransaction::create($data + ['type' => $type, 'user_id' => $request->user()->id]);
            $transaction->items()->create(['product_id' => $product->id, 'qty' => $data['qty'], 'price' => $data['price'] ?? ($type === 'in' ? $product->cost_price : $product->selling_price)]);
            $product->current_stock += $type === 'in' ? $data['qty'] : -$data['qty'];
            $product->save();
            StockHistory::create(['product_id' => $product->id, 'user_id' => $request->user()->id, 'occurred_at' => now(), 'quantity_in' => $type === 'in' ? $data['qty'] : 0, 'quantity_out' => $type === 'out' ? $data['qty'] : 0, 'balance' => $product->current_stock, 'activity' => $type === 'in' ? 'Barang masuk' : 'Barang keluar', 'reference_type' => StockTransaction::class, 'reference_id' => $transaction->id]);
        });

        return redirect()->route('stock.'.($type === 'in' ? 'inbound' : 'outbound'))->with('success', 'Transaksi berhasil, stok telah diperbarui.');
    }

    public function storeInbound(Request $request) { return $this->save($request, 'in'); }
    public function storeOutbound(Request $request) { return $this->save($request, 'out'); }
    public function history() { return view('stock.history', ['rows' => StockHistory::with(['product', 'user'])->latest('occurred_at')->paginate(20)]); }
}
