<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Salesperson;
use App\Models\StockHistory;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MasterDataController extends Controller
{
    private function meta(Request $request): array
    {
        $key = explode('.', $request->route()->getName())[0];

        return match ($key) {
            'products' => [Product::class, 'Produk'],
            'suppliers' => [Supplier::class, 'Supplier'],
            'warehouses' => [Warehouse::class, 'Gudang'],
            default => [Salesperson::class, 'Sales'],
        };
    }

    private function rules(string $class, ?int $id = null): array
    {
        return match ($class) {
            Product::class => [
                'name' => 'required|max:150',
                'code' => ['required', 'max:50', Rule::unique('products')->ignore($id)],
                'sku' => ['required', 'max:50', Rule::unique('products')->ignore($id)],
                'warehouse_id' => 'required|exists:warehouses,id',
                'supplier_id' => 'nullable|exists:suppliers,id',
                'unit' => 'required|max:20',
                'cost_price' => 'required|numeric|min:0',
                'selling_price' => 'required|numeric|min:0',
                'minimum_stock' => 'required|integer|min:0',
                'current_stock' => 'required|integer|min:0',
                'status' => 'required|in:active,inactive',
            ],
            Supplier::class => ['name' => 'required|max:150', 'email' => 'nullable|email', 'phone' => 'nullable|max:30'],
            Warehouse::class => ['name' => 'required|max:150'],
            default => ['name' => 'required|max:150', 'email' => 'nullable|email', 'status' => 'required|in:active,inactive'],
        };
    }

    private function context(Request $request, $row = null): array
    {
        [$class, $title] = $this->meta($request);

        return [
            'row' => $row ?? new $class,
            'title' => $title,
            'resource' => explode('.', $request->route()->getName())[0],
            'suppliers' => Supplier::all(),
            'warehouses' => Warehouse::all(),
        ];
    }

    private function optionalFields(Request $request): array
    {
        return $request->only(['pic', 'phone', 'address', 'city', 'npwp', 'notes', 'branch', 'area', 'category', 'brand', 'rack_location']);
    }

    private function normalizeMoney(Request $request): void
    {
        foreach (['cost_price', 'selling_price'] as $field) {
            if ($request->has($field)) {
                $value = str_replace('.', '', (string) $request->input($field));
                $request->merge([$field => str_replace(',', '.', $value)]);
            }
        }
    }

    private function recordStockChange(Product $product, int $difference, Request $request, string $activity): void
    {
        if ($difference === 0) {
            return;
        }

        StockHistory::create([
            'product_id' => $product->id,
            'user_id' => $request->user()->id,
            'occurred_at' => now(),
            'quantity_in' => max(0, $difference),
            'quantity_out' => max(0, -$difference),
            'balance' => $product->current_stock,
            'activity' => $activity,
            'reference_type' => Product::class,
            'reference_id' => $product->id,
        ]);
    }

    public function index(Request $request)
    {
        [$class, $title] = $this->meta($request);
        $query = $class::query();

        if ($search = $request->get('q')) {
            $query->where('name', 'like', "%{$search}%");
        }

        return view('master.index', [
            'rows' => $query->latest()->paginate(12)->withQueryString(),
            'title' => $title,
            'resource' => explode('.', $request->route()->getName())[0],
            'suppliers' => $class === Product::class ? Supplier::orderBy('name')->get() : collect(),
            'warehouses' => $class === Product::class ? Warehouse::orderBy('name')->get() : collect(),
        ]);
    }

    public function show(Request $request, $id)
    {
        [$class] = $this->meta($request);
        $row = $class::findOrFail($id);

        if ($row instanceof Product) {
            $row->load(['supplier', 'warehouse', 'histories' => fn ($query) => $query->latest('occurred_at')->limit(8)]);
        }
        if ($row instanceof Supplier || $row instanceof Warehouse) {
            $row->loadCount('products');
        }

        return view('master.show', $this->context($request, $row));
    }

    public function create(Request $request)
    {
        return view('master.form', $this->context($request));
    }

    public function store(Request $request)
    {
        [$class] = $this->meta($request);
        $this->normalizeMoney($request);
        $data = $request->validate($this->rules($class));

        if ($class === Product::class) {
            $data['barcode'] = 'AUTO-'.Str::uuid();
        }

        DB::transaction(function () use ($class, $data, $request): void {
            $row = $class::create($data + $this->optionalFields($request));

            if ($row instanceof Product) {
                $this->recordStockChange($row, (int) $row->current_stock, $request, 'Stok awal produk');
            }
        });

        return redirect()->route(explode('.', $request->route()->getName())[0].'.index')->with('success', 'Data berhasil disimpan.');
    }

    public function edit(Request $request, $id)
    {
        [$class] = $this->meta($request);

        return view('master.form', $this->context($request, $class::findOrFail($id)));
    }

    public function update(Request $request, $id)
    {
        [$class] = $this->meta($request);
        $this->normalizeMoney($request);
        $data = $request->validate($this->rules($class, $id));

        DB::transaction(function () use ($class, $data, $request, $id): void {
            $row = $class::lockForUpdate()->findOrFail($id);
            $previousStock = $row instanceof Product ? (int) $row->current_stock : 0;
            $row->update($data + $this->optionalFields($request));

            if ($row instanceof Product) {
                $this->recordStockChange($row, (int) $row->current_stock - $previousStock, $request, 'Penyesuaian stok produk');
            }
        });

        return redirect()->route(explode('.', $request->route()->getName())[0].'.index')->with('success', 'Data diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        [$class] = $this->meta($request);
        $class::findOrFail($id)->delete();

        return back()->with('success', 'Data diarsipkan.');
    }
}
