<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\StockHistory;
use App\Models\StockTransaction;
use App\Models\StockTransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesReturnController extends Controller
{
    public function create(Request $request, StockTransaction $sale)
    {
        $this->authorizeSale($request, $sale);
        $sale->load(['user', 'items.product', 'items.salesReturnItems']);

        foreach ($sale->items as $item) {
            $item->returned_qty = $item->salesReturnItems->sum('qty');
            $item->remaining_qty = max(0, $item->qty - $item->returned_qty);
        }

        return view('stock.return-form', compact('sale'));
    }

    public function store(Request $request, StockTransaction $sale)
    {
        $this->authorizeSale($request, $sale);
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.stock_transaction_item_id' => ['required', 'integer', 'distinct', 'exists:stock_transaction_items,id'],
            'items.*.qty' => ['required', 'integer', 'min:0'],
        ]);

        $createdReturn = DB::transaction(function () use ($request, $sale, $data): SalesReturn {
            $lockedSale = StockTransaction::query()->lockForUpdate()->findOrFail($sale->id);
            if ($lockedSale->type !== 'out') {
                abort(404);
            }

            $soldItems = StockTransactionItem::query()
                ->where('stock_transaction_id', $lockedSale->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $returnLines = [];
            $refundTotal = 0;

            foreach ($data['items'] as $line) {
                $soldItem = $soldItems->get((int) $line['stock_transaction_item_id']);
                if (!$soldItem) {
                    throw ValidationException::withMessages(['items' => 'Barang retur harus berasal dari transaksi penjualan ini.']);
                }

                $qty = (int) $line['qty'];
                if ($qty === 0) {
                    continue;
                }

                $alreadyReturned = SalesReturnItem::query()
                    ->where('stock_transaction_item_id', $soldItem->id)
                    ->sum('qty');
                $remaining = (int) $soldItem->qty - (int) $alreadyReturned;
                if ($qty > $remaining) {
                    throw ValidationException::withMessages([
                        'items' => "Jumlah retur {$soldItem->product?->name} melebihi sisa barang yang dapat diretur ({$remaining}).",
                    ]);
                }

                $product = Product::withTrashed()->lockForUpdate()->findOrFail($soldItem->product_id);
                $costPrice = $soldItem->cost_price ?? $product->cost_price ?? 0;
                $refundTotal += (float) $soldItem->price * $qty;
                $returnLines[] = compact('soldItem', 'product', 'qty', 'costPrice');
            }

            if ($returnLines === []) {
                throw ValidationException::withMessages(['items' => 'Pilih jumlah barang yang akan diretur.']);
            }

            $salesReturn = SalesReturn::create([
                'stock_transaction_id' => $lockedSale->id,
                'user_id' => $request->user()->id,
                'returned_at' => now(),
                'reason' => $data['reason'],
                'refund_total' => $refundTotal,
            ]);
            $salesReturn->update(['return_no' => 'RT-'.str_pad((string) $salesReturn->id, 6, '0', STR_PAD_LEFT)]);

            foreach ($returnLines as $line) {
                /** @var StockTransactionItem $soldItem */
                $soldItem = $line['soldItem'];
                /** @var Product $product */
                $product = $line['product'];
                $qty = $line['qty'];

                $salesReturn->items()->create([
                    'stock_transaction_item_id' => $soldItem->id,
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'price' => $soldItem->price,
                    'cost_price' => $line['costPrice'],
                ]);

                $product->current_stock += $qty;
                $product->save();

                StockHistory::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'occurred_at' => now(),
                    'quantity_in' => $qty,
                    'quantity_out' => 0,
                    'balance' => $product->current_stock,
                    'activity' => 'Retur penjualan '.$salesReturn->return_no,
                    'reference_type' => SalesReturn::class,
                    'reference_id' => $salesReturn->id,
                ]);
            }

            return $salesReturn;
        });

        return redirect()->route('sales.history')->with(
            'success',
            "Retur {$createdReturn->return_no} berhasil dicatat. Stok bertambah dan nilai pengembalian tercatat Rp ".number_format((float) $createdReturn->refund_total, 0, ',', '.').'.'
        );
    }

    public function history(Request $request)
    {
        $query = SalesReturn::with(['sale', 'user', 'items.product'])
            ->latest('returned_at')
            ->latest('id');

        if ($request->user()->hasRole('Kasir')) {
            $query->where('user_id', $request->user()->id);
        }

        $perPage = (int) $request->input('per_page', 10);
        abort_unless(in_array($perPage, [10, 25, 50, 100], true), 422);

        return view('stock.returns-history', ['returns' => $query->paginate($perPage)->withQueryString(), 'perPage' => $perPage]);
    }

    private function authorizeSale(Request $request, StockTransaction $sale): void
    {
        abort_unless($sale->type === 'out', 404);
        abort_if($request->user()->hasRole('Kasir') && (int) $sale->user_id !== (int) $request->user()->id, 403);
    }
}
