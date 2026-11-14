<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page{margin:26px 30px}body{font-family:DejaVu Sans,sans-serif;color:#292742;font-size:9px}
        .brand{font-size:9px;font-weight:bold;letter-spacing:1.5px;color:#5942b8}.meta{float:right;text-align:right;color:#77748a;font-size:8px;line-height:1.6}
        h1{font-size:20px;margin:8px 0 4px}.subtitle{color:#77748a;margin:0 0 17px}.summary{width:100%;border-collapse:separate;border-spacing:5px;margin:0 -5px 15px}.summary td{width:25%;background:#f3f1fa;border-radius:5px;padding:10px}.summary small{font-size:7px;color:#77748a;text-transform:uppercase}.summary strong{display:block;margin-top:6px;font-size:13px;color:#34247e}
        h2{font-size:11px;margin:16px 0 7px}table.data{width:100%;border-collapse:collapse;table-layout:fixed}table.data th{background:#f0eef7;text-align:left;color:#514a70;font-size:8px;text-transform:uppercase;padding:8px 7px;border-bottom:1px solid #dedbea}table.data td{padding:8px 7px;border-bottom:1px solid #e9e7ef;font-size:8px;vertical-align:top;overflow-wrap:anywhere}table.data tbody tr:nth-child(even) td{background:#fbfaff}table.data tfoot td{font-weight:bold;background:#f1eff8;border-top:1px solid #dedbea}.num{text-align:right!important;white-space:nowrap;font-variant-numeric:tabular-nums}.muted{color:#77748a}.footer{position:fixed;bottom:-12px;left:0;right:0;border-top:1px solid #ddd9eb;padding-top:5px;color:#77748a;font-size:7px}
    </style>
</head>
<body>
    <div class="meta">{{$periodLabel}}<br>Dicetak {{now()->translatedFormat('d F Y, H:i')}} WIB<br>Oleh {{auth()->user()->name}}</div>
    <div class="brand">STOCKFLOW ERP · LAPORAN OPERASIONAL</div>
    <h1>{{$type==='inventory'?'Persediaan Saat Ini':($type==='inventory-history'?'Riwayat Stok':($type==='inbound'?'Laporan Barang Masuk':'Laporan Barang Keluar'))}}</h1>
    <p class="subtitle">Rekap data operasional StockFlow untuk {{$periodLabel}}.</p>

    @if($type==='inventory')
        <table class="summary"><tr><td><small>Jenis Produk</small><strong>{{$totalProducts}}</strong></td><td><small>Total Unit</small><strong>{{number_format($totalUnits,0,',','.')}}</strong></td><td><small>Nilai Persediaan</small><strong>Rp {{number_format($totalValue,0,',','.')}}</strong></td><td><small>Snapshot</small><strong>{{today()->format('d/m/Y')}}</strong></td></tr></table>
        <table class="data"><colgroup><col style="width:14%"><col style="width:24%"><col style="width:18%"><col style="width:12%"><col style="width:16%"><col style="width:16%"></colgroup><thead><tr><th>Kode</th><th>Produk</th><th>Kategori</th><th class="num">Stok</th><th class="num">Harga Modal</th><th class="num">Nilai Stok</th></tr></thead><tbody>
            @forelse($rows as $product)<tr><td>{{$product->code}}</td><td>{{$product->name}}<br><span class="muted">{{$product->sku}}</span></td><td>{{$product->category?:'-'}}</td><td class="num">{{$product->current_stock}} {{$product->unit}}</td><td class="num">Rp {{number_format($product->cost_price,0,',','.')}}</td><td class="num">Rp {{number_format($product->current_stock*$product->cost_price,0,',','.')}}</td></tr>@empty<tr><td colspan="6" class="muted">Belum ada produk.</td></tr>@endforelse
        </tbody></table>
    @elseif($type==='inventory-history')
        <table class="summary"><tr><td><small>Barang Masuk</small><strong>+{{number_format($totalInbound,0,',','.')}} unit</strong></td><td><small>Barang Keluar</small><strong>−{{number_format($totalOutbound,0,',','.')}} unit</strong></td><td><small>Saldo Akhir</small><strong>{{number_format($totalUnits,0,',','.')}} unit</strong></td><td><small>Nilai Akhir</small><strong>Rp {{number_format($totalValue,0,',','.')}}</strong></td></tr></table>
        <table class="data"><colgroup><col style="width:28%"><col style="width:16%"><col style="width:16%"><col style="width:16%"><col style="width:24%"></colgroup><thead><tr><th>Tanggal</th><th class="num">Saldo Unit</th><th class="num">Barang Masuk</th><th class="num">Barang Keluar</th><th class="num">Nilai Persediaan</th></tr></thead><tbody>
            @forelse($rows as $row)<tr><td>{{$row['date']->translatedFormat('d F Y')}}</td><td class="num">{{number_format($row['units'],0,',','.')}}</td><td class="num">+{{number_format($row['inbound'],0,',','.')}}</td><td class="num">−{{number_format($row['outbound'],0,',','.')}}</td><td class="num">Rp {{number_format($row['value'],0,',','.')}}</td></tr>@empty<tr><td colspan="5" class="muted">Tidak ada pergerakan stok.</td></tr>@endforelse
        </tbody></table>
    @else
        <table class="summary"><tr><td><small>Jumlah Transaksi</small><strong>{{$totalTransactions}}</strong></td><td><small>Total Unit</small><strong>{{number_format($totalUnits,0,',','.')}}</strong></td><td><small>Total Nilai</small><strong>Rp {{number_format($totalAmount,0,',','.')}}</strong></td><td><small>Periode</small><strong>{{$periodLabel}}</strong></td></tr></table>
        <table class="data"><colgroup><col style="width:12%"><col style="width:15%"><col style="width:22%"><col style="width:10%"><col style="width:15%"><col style="width:15%"><col style="width:11%"></colgroup><thead><tr><th>Tanggal</th><th>Referensi</th><th>Produk</th><th class="num">Qty</th><th class="num">Harga / Unit</th><th class="num">Subtotal</th><th>Dicatat Oleh</th></tr></thead><tbody>
            @php($grandTotal=0)
            @forelse($rows as $transaction)@foreach($transaction->items as $item)@php($subtotal=$item->qty*$item->price)@php($grandTotal+=$subtotal)<tr><td>{{$transaction->transaction_date->format('d/m/Y')}}</td><td>{{$transaction->reference_no?:'TR-'.$transaction->id}}</td><td>{{$item->product?->name??'Produk dihapus'}}<br><span class="muted">{{$transaction->destination?:$transaction->notes?:''}}</span></td><td class="num">{{$item->qty}} {{$item->product?->unit}}</td><td class="num">Rp {{number_format($item->price,0,',','.')}}</td><td class="num">Rp {{number_format($subtotal,0,',','.')}}</td><td>{{$transaction->user?->name??'-'}}</td></tr>@endforeach @empty<tr><td colspan="7" class="muted">Tidak ada transaksi pada rentang ini.</td></tr>@endforelse
        </tbody>@if($rows->isNotEmpty())<tfoot><tr><td colspan="5" class="num">Total</td><td class="num">Rp {{number_format($grandTotal,0,',','.')}}</td><td></td></tr></tfoot>@endif</table>
    @endif
    <div class="footer">Laporan operasional StockFlow ERP · Dibuat otomatis dari transaksi pada sistem</div>
</body>
</html>
