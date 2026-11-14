<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page{margin:30px 34px 38px}
        body{font-family:DejaVu Sans,sans-serif;color:#25233a;font-size:9px}
        .topline{height:5px;background:#6548d8;margin:-30px -34px 18px}
        .brand{font-size:9px;color:#6552b8;font-weight:bold;letter-spacing:1.5px}
        .title{font-size:23px;font-weight:bold;margin:7px 0 4px}
        .subtitle{color:#77748a;font-size:9px;margin-bottom:13px}
        .period{background:#f5f3fb;border-left:3px solid #6548d8;padding:9px 11px;color:#55516e;margin-bottom:12px}
        .summary{width:100%;border-collapse:separate;border-spacing:4px;margin:0 -4px 13px;table-layout:fixed}
        .summary td{background:#f5f3fb;padding:11px 12px;border:1px solid #ece9f6;width:25%}
        .summary td.highlight{background:#f0ecff;border-color:#ded5ff}
        .label{font-size:8px;color:#77748a;letter-spacing:.5px}
        .value{font-size:14px;font-weight:bold;margin-top:6px}.purple{color:#583ec4}
        .section{margin:16px 0 7px;border-bottom:1px solid #e5e1ef;padding-bottom:6px}
        h2{font-size:11px;margin:0 0 3px;color:#302078}
        .section small{font-size:8px;color:#77748a}
        table.data{width:100%;border-collapse:collapse;table-layout:fixed}
        table.summary-data{width:100%;margin-left:0}
        table.data th{background:#eeebf7;color:#504b68;text-align:left;font-size:8.5px;padding:8px 6px;border-bottom:1px solid #ded9ed}
        table.data td{border-bottom:1px solid #e9e7ef;padding:8px 6px;font-size:8.5px;vertical-align:top}
        table.data tbody tr:nth-child(even) td{background:#fbfaff}
        table.data tbody tr.return-row td{background:#fff8f4}
        table.data tfoot td{font-weight:bold;background:#f2f0f8;border-top:1px solid #dcd7e9}
        .right{text-align:right;white-space:nowrap}table.data th.right{text-align:right}.nowrap{white-space:nowrap}.profit{color:#4e32b9;font-weight:bold}.negative{color:#bd3030;font-weight:bold}.muted{color:#77748a}
        .note{background:#fff8e6;padding:8px;margin-top:12px;color:#705921}
        .footer{position:fixed;bottom:-22px;left:0;right:0;border-top:1px solid #ddd9eb;padding-top:6px;color:#77748a;font-size:8px}
    </style>
</head>
<body>
    <div class="topline"></div>
    <div class="brand">STOCKFLOW | LAPORAN MANAJEMEN</div>
    <div class="title">Laporan Keuntungan Penjualan</div>
    <div class="subtitle">Dicetak {{now()->translatedFormat('d F Y, H:i')}} WIB</div>
    <div class="period"><strong>Periode laporan</strong> &nbsp; {{$periodLabel}}</div>

    <table class="summary"><tr>
        <td><div class="label">TOTAL OMZET</div><div class="value">Rp {{number_format($totalRevenue,0,',','.')}}</div></td>
        <td><div class="label">MODAL TERJUAL</div><div class="value">Rp {{number_format($totalCost,0,',','.')}}</div></td>
        <td class="highlight"><div class="label">LABA KOTOR</div><div class="value purple">Rp {{number_format($totalProfit,0,',','.')}}</div></td>
        <td><div class="label">JUMLAH TRANSAKSI</div><div class="value">{{$totalTransactions}}</div></td>
    </tr></table>

    <div class="section"><h2>Ringkasan {{$period==='daily'?'Harian':'Per Hari'}}</h2><small>Rekap omzet, modal barang terjual, dan laba kotor.</small></div>
    <table class="data summary-data"><colgroup><col style="width:24%"><col style="width:14%"><col style="width:21%"><col style="width:21%"><col style="width:20%"></colgroup><thead><tr><th>Tanggal</th><th class="right">Transaksi</th><th class="right">Omzet</th><th class="right">Modal</th><th class="right">Laba Kotor</th></tr></thead><tbody>
        @forelse($rows as $row)<tr><td class="nowrap">{{$row['date']->translatedFormat('d M Y')}}</td><td class="right">{{$row['transactions']}}</td><td class="right">Rp {{number_format($row['revenue'],0,',','.')}}</td><td class="right">Rp {{number_format($row['cost'],0,',','.')}}</td><td class="right {{$row['profit']<0?'negative':'profit'}}">Rp {{number_format($row['profit'],0,',','.')}}</td></tr>@empty<tr><td colspan="5" class="muted">Tidak ada penjualan dalam periode ini.</td></tr>@endforelse
    </tbody><tfoot><tr><td>Total</td><td class="right">{{$totalTransactions}}</td><td class="right">Rp {{number_format($totalRevenue,0,',','.')}}</td><td class="right">Rp {{number_format($totalCost,0,',','.')}}</td><td class="right">Rp {{number_format($totalProfit,0,',','.')}}</td></tr></tfoot></table>

    <div class="section"><h2>Rincian Penjualan</h2><small>Detail produk pada setiap transaksi dalam periode laporan.</small></div>
    <table class="data"><colgroup><col style="width:9%"><col style="width:9%"><col style="width:13%"><col style="width:12%"><col style="width:17%"><col style="width:6%"><col style="width:12%"><col style="width:11%"><col style="width:11%"></colgroup><thead><tr><th>Tanggal</th><th>Jenis</th><th>No. Transaksi</th><th>Kasir</th><th>Produk</th><th class="right">Qty</th><th class="right">Omzet</th><th class="right">Modal</th><th class="right">Laba</th></tr></thead><tbody>
        @forelse($details as $detail)
            <tr class="{{$detail['kind']==='Retur'?'return-row':''}}"><td class="nowrap">{{$detail['date']->format('d/m/Y')}}</td><td>{{$detail['kind']}}</td><td class="nowrap">{{$detail['reference']}}</td><td>{{$detail['cashier']}}</td><td>{{$detail['product']}}</td><td class="right">{{$detail['qty']}}</td><td class="right">Rp {{number_format($detail['revenue'],0,',','.')}}</td><td class="right">Rp {{number_format($detail['cost'],0,',','.')}}</td><td class="right {{$detail['profit']<0?'negative':'profit'}}">Rp {{number_format($detail['profit'],0,',','.')}}</td></tr>
        @empty<tr><td colspan="9" class="muted">Tidak ada transaksi atau retur pada periode ini.</td></tr>@endforelse
    </tbody></table>
    @if($legacyCostItems > 0)<div class="note">Catatan: {{$legacyCostItems}} item transaksi lama tidak memiliki snapshot harga modal; laporan memakai harga modal produk saat ini.</div>@endif
    <div class="footer">StockFlow ERP | Laba kotor = omzet penjualan - modal barang terjual</div>
</body>
</html>
