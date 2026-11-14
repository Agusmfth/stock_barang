<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{$type==='inventory'?'Laporan Persediaan':($type==='inventory-history'?'Riwayat Stok':($type==='inbound'?'Laporan Barang Masuk':'Laporan Barang Keluar'))}} — StockFlow</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f4f3f8;color:#292742;font:13px Arial,sans-serif}.page{max-width:1180px;margin:28px auto;background:#fff;padding:38px 42px;border-radius:14px;box-shadow:0 8px 28px #30296c10}
        .toolbar{max-width:1180px;margin:20px auto 0;display:flex;justify-content:flex-end;gap:8px}.toolbar button{border:0;border-radius:8px;background:#6847ed;color:#fff;padding:11px 17px;font-weight:700;cursor:pointer}.toolbar a{border:1px solid #ddd8ef;border-radius:8px;color:#4e32c5;padding:10px 15px;text-decoration:none;font-weight:700}
        .brand{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #6244dc;padding-bottom:18px}.brand-name{font-size:15px;letter-spacing:1.7px;font-weight:800;color:#4c35b2}.brand-sub{font-size:10px;letter-spacing:1.3px;color:#858199;margin-top:4px}.print-meta{text-align:right;color:#77748a;font-size:11px;line-height:1.7}
        h1{font-size:23px;margin:22px 0 5px;color:#292742}.subtitle{color:#77748a;margin-bottom:20px}.summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:18px 0 22px}.metric{border:1px solid #e9e6f2;background:#faf9fd;border-radius:9px;padding:13px}.metric small{display:block;color:#77748a;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}.metric strong{display:block;margin-top:7px;font-size:16px;color:#34247e}
        table{width:100%;border-collapse:collapse;margin-top:14px}thead{display:table-header-group}th{background:#f0eef7;text-align:left;color:#514a70;font-size:10px;text-transform:uppercase;letter-spacing:.45px;padding:10px 9px;border-bottom:1px solid #dedbea}td{padding:9px;border-bottom:1px solid #eeedf3;vertical-align:top}tbody tr:nth-child(even){background:#fcfbfe}.num{text-align:right;white-space:nowrap}.text-right{text-align:right}.empty{text-align:center;color:#858199;padding:28px}.footnote{margin-top:18px;color:#858199;font-size:10px;border-top:1px solid #e9e6f2;padding-top:10px}.status{display:inline-block;background:#f0edff;color:#5034c3;border-radius:20px;padding:4px 8px;font-size:10px;font-weight:700}
        @media print{@page{size:A4 landscape;margin:13mm}body{background:#fff;font-size:10px}.page{max-width:none;margin:0;padding:0;border-radius:0;box-shadow:none}.toolbar{display:none}.summary{break-inside:avoid}.metric{padding:9px}h1{font-size:19px}tr{break-inside:avoid}th,td{padding:7px 6px}.footnote{position:fixed;bottom:0;left:0;right:0}}
        @media(max-width:700px){.page{margin:12px;padding:22px}.toolbar{margin:12px}.summary{grid-template-columns:repeat(2,minmax(0,1fr))}.brand{gap:12px}.print-meta{font-size:10px}}
    </style>
</head>
<body>
<div class="toolbar"><a href="{{route('reports',$type)}}">Kembali</a><button onclick="window.print()"><span>▣</span> Cetak / Simpan PDF</button></div>
<main class="page">
    <header class="brand"><div><div class="brand-name">STOCKFLOW</div><div class="brand-sub">LAPORAN OPERASIONAL</div></div><div class="print-meta">{{$periodLabel}}<br>Dicetak {{now()->translatedFormat('d F Y, H:i')}} WIB<br>Oleh {{auth()->user()->name}}</div></header>
    <h1>{{$type==='inventory'?'Laporan Persediaan Saat Ini':($type==='inventory-history'?'Laporan Riwayat Stok':($type==='inbound'?'Laporan Barang Masuk':'Laporan Barang Keluar'))}}</h1>
    <div class="subtitle">{{$type==='inventory'?'Snapshot jumlah dan nilai persediaan produk.':($type==='inventory-history'?'Ringkasan pergerakan dan saldo stok pada rentang tanggal terpilih.':'Rincian transaksi barang pada rentang tanggal terpilih.')}}</div>

    @if($type==='inventory')
        <section class="summary"><div class="metric"><small>Jenis Produk</small><strong>{{$totalProducts}}</strong></div><div class="metric"><small>Total Unit</small><strong>{{number_format($totalUnits,0,',','.')}}</strong></div><div class="metric"><small>Nilai Persediaan</small><strong>Rp {{number_format($totalValue,0,',','.')}}</strong></div><div class="metric"><small>Tanggal Snapshot</small><strong>{{today()->format('d/m/Y')}}</strong></div></section>
        <table><thead><tr><th>Kode</th><th>Produk</th><th>Kategori</th><th class="num">Stok</th><th class="num">Harga Modal</th><th class="num">Nilai Stok</th></tr></thead><tbody>
            @forelse($rows as $product)<tr><td>{{$product->code}}</td><td><strong>{{$product->name}}</strong><br><small>{{$product->sku}}</small></td><td>{{$product->category?:'-'}}</td><td class="num">{{$product->current_stock}} {{$product->unit}}</td><td class="num">Rp {{number_format($product->cost_price,0,',','.')}}</td><td class="num">Rp {{number_format($product->current_stock*$product->cost_price,0,',','.')}}</td></tr>@empty<tr><td colspan="6" class="empty">Belum ada produk.</td></tr>@endforelse
        </tbody></table>
    @elseif($type==='inventory-history')
        <section class="summary"><div class="metric"><small>Barang Masuk</small><strong>{{number_format($totalInbound,0,',','.')}} unit</strong></div><div class="metric"><small>Barang Keluar</small><strong>{{number_format($totalOutbound,0,',','.')}} unit</strong></div><div class="metric"><small>Sisa Stok Akhir</small><strong>{{number_format($totalUnits,0,',','.')}} unit</strong></div><div class="metric"><small>Nilai Persediaan Akhir</small><strong>Rp {{number_format($totalValue,0,',','.')}}</strong></div></section>
        <table><thead><tr><th>Tanggal</th><th class="num">Saldo Unit</th><th class="num">Barang Masuk</th><th class="num">Barang Keluar</th><th class="num">Nilai Persediaan</th></tr></thead><tbody>
            @forelse($rows as $row)<tr><td>{{$row['date']->translatedFormat('d F Y')}}</td><td class="num">{{number_format($row['units'],0,',','.')}}</td><td class="num">+{{number_format($row['inbound'],0,',','.')}}</td><td class="num">−{{number_format($row['outbound'],0,',','.')}}</td><td class="num">Rp {{number_format($row['value'],0,',','.')}}</td></tr>@empty<tr><td colspan="5" class="empty">Tidak ada pergerakan stok.</td></tr>@endforelse
        </tbody></table>
    @else
        <section class="summary"><div class="metric"><small>Jumlah Transaksi</small><strong>{{$totalTransactions}}</strong></div><div class="metric"><small>Total Unit</small><strong>{{number_format($totalUnits,0,',','.')}}</strong></div><div class="metric"><small>{{$type==='inbound'?'Nilai Barang Masuk':'Nilai Barang Keluar'}}</small><strong>Rp {{number_format($totalAmount,0,',','.')}}</strong></div><div class="metric"><small>Rentang</small><strong>{{$periodLabel}}</strong></div></section>
        <table><thead><tr><th>Tanggal</th><th>Referensi</th><th>Produk</th><th class="num">Qty</th><th class="num">Harga / Unit</th><th class="num">Subtotal</th><th>Dicatat Oleh</th></tr></thead><tbody>
            @php($grandTotal=0)
            @forelse($rows as $transaction)@foreach($transaction->items as $item)@php($subtotal=$item->qty*$item->price)@php($grandTotal+=$subtotal)<tr><td>{{$transaction->transaction_date->format('d/m/Y')}}</td><td>{{$transaction->reference_no?:'TR-'.$transaction->id}}</td><td><strong>{{$item->product?->name??'Produk dihapus'}}</strong><br><small>{{$transaction->destination?:$transaction->notes?:''}}</small></td><td class="num">{{$item->qty}} {{$item->product?->unit}}</td><td class="num">Rp {{number_format($item->price,0,',','.')}}</td><td class="num">Rp {{number_format($subtotal,0,',','.')}}</td><td>{{$transaction->user?->name??'-'}}</td></tr>@endforeach @empty<tr><td colspan="7" class="empty">Tidak ada transaksi pada rentang ini.</td></tr>@endforelse
            @if($rows->isNotEmpty())<tr><td colspan="5" class="text-right"><strong>Total</strong></td><td class="num"><strong>Rp {{number_format($grandTotal,0,',','.')}}</strong></td><td></td></tr>@endif
        </tbody></table>
    @endif
    <div class="footnote">Dokumen dibuat oleh StockFlow ERP. Pastikan periode laporan sesuai sebelum digunakan sebagai arsip operasional.</div>
</main>
<script>window.addEventListener('load',()=>setTimeout(()=>window.print(),250));</script>
</body>
</html>
