@extends('layouts.app')
@section('title','Dashboard Admin')
@section('content')
<style>
    .admin-welcome{display:flex;justify-content:space-between;align-items:center;gap:20px;padding:24px 26px;border-radius:16px;background:linear-gradient(110deg,#302078,#6548d8 62%,#8d67e8);color:#fff}
    .admin-welcome h2{font:800 21px 'Plus Jakarta Sans';margin:0 0 5px}.admin-welcome p{margin:0;color:#e6deff;font-size:13px}
    .admin-actions{display:flex;gap:9px;flex-wrap:wrap}.admin-actions .btn{white-space:nowrap}
    .admin-metric{height:100%;padding:17px 18px;border:1px solid #efedf5;border-radius:13px;background:#fff}
    .admin-metric-top{display:flex;justify-content:space-between;align-items:center;gap:10px}.admin-metric-label{color:#77748a;font-size:12px;font-weight:600}
    .admin-metric-value{font:800 22px 'Plus Jakarta Sans';letter-spacing:-.6px;color:#292742;margin-top:13px;white-space:nowrap}
    .admin-metric-note{font-size:11px;color:#858199;margin-top:3px}.admin-metric-icon{width:38px;height:38px;display:grid;place-items:center;border-radius:11px;font-size:15px}
    .stock-overview{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.stock-overview-item{padding:15px 17px;border:1px solid #efedf5;border-radius:12px;background:#fff}.stock-overview-item small{display:block;color:#858199}.stock-overview-item strong{display:block;margin-top:5px;font:700 17px 'Plus Jakarta Sans';color:#292742}
    .admin-section-title{font:700 15px 'Plus Jakarta Sans';margin:0 0 4px;color:#292742}.admin-section-subtitle{font-size:12px;color:#858199}.chart-box{height:270px}.stock-alert-item{padding:12px 0;border-bottom:1px solid #f0eef5}.stock-alert-item:last-child{border-bottom:0}.stock-alert-name{font-size:12px;font-weight:700;color:#343149}.stock-alert-detail{font-size:11px;color:#858199;margin-top:3px}.stock-alert-qty{font-size:12px;font-weight:700;color:#c93846;white-space:nowrap}
    .activity-table{min-width:650px}.activity-table th,.activity-table td{padding:11px 13px!important;vertical-align:middle}.activity-table td:last-child{text-align:right;white-space:nowrap}
    @media(max-width:900px){.admin-welcome{align-items:flex-start;flex-direction:column}.stock-overview{grid-template-columns:1fr}}
    @media(max-width:575px){.admin-metric-value{font-size:19px}.admin-actions{width:100%}.admin-actions .btn{flex:1}}
</style>

<section class="admin-welcome mb-4">
    <div><h2>Halo, {{strtok(auth()->user()->name,' ')}}</h2><p>Ringkasan penjualan, keuntungan, dan kondisi stok hari ini.</p></div>
    <div class="admin-actions">
        <a href="{{route('stock.outbound')}}" class="btn btn-light text-primary"><i class="fa-solid fa-cart-shopping me-2"></i>Input Penjualan</a>
        <a href="{{route('stock.inbound')}}" class="btn btn-outline-light"><i class="fa-solid fa-arrow-down me-2"></i>Barang Masuk</a>
        <a href="{{route('reports.profit')}}" class="btn btn-outline-light"><i class="fa-solid fa-chart-line me-2"></i>Laporan Keuntungan</a>
    </div>
</section>

<div class="row g-3 mb-4">
    @foreach([
        ['Penjualan Hari Ini','Rp '.number_format($todayRevenue,0,',','.'),'fa-sack-dollar','#f0edff','#6548d8','Total omzet transaksi hari ini'],
        ['Laba Kotor Hari Ini','Rp '.number_format($todayProfit,0,',','.'),'fa-chart-line','#e8f9f1','#159969','Omzet dikurangi modal barang'],
        ['Barang Masuk Hari Ini',number_format($todayInboundUnits,0,',','.').' unit','fa-arrow-down','#e7f7f4','#14a38b','Jumlah unit yang diterima'],
        ['Barang Keluar Hari Ini',number_format($todayOutboundUnits,0,',','.').' unit','fa-arrow-up','#fff3e5','#e98522','Jumlah unit terjual atau keluar'],
    ] as [$label,$valueText,$icon,$bg,$color,$note])
        <div class="col-sm-6 col-xl-3"><div class="admin-metric">
            <div class="admin-metric-top"><span class="admin-metric-label">{{$label}}</span><span class="admin-metric-icon" style="background:{{$bg}};color:{{$color}}"><i class="fa-solid {{$icon}}"></i></span></div>
            <div class="admin-metric-value">{{$valueText}}</div><div class="admin-metric-note">{{$note}}</div>
        </div></div>
    @endforeach
</div>

<section class="mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-2"><div><h3 class="admin-section-title">Kondisi Persediaan</h3><div class="admin-section-subtitle">Nilai stok menggunakan harga modal produk saat ini.</div></div><a href="{{route('products.index')}}" class="small text-decoration-none fw-bold">Lihat daftar barang <i class="fa-solid fa-arrow-right ms-1"></i></a></div>
    <div class="stock-overview">
        <div class="stock-overview-item"><small>Jenis Produk Aktif</small><strong>{{number_format($products,0,',','.')}} produk</strong></div>
        <div class="stock-overview-item"><small>Total Unit Tersedia</small><strong>{{number_format($stockUnits,0,',','.')}} unit</strong></div>
        <div class="stock-overview-item"><small>Nilai Persediaan</small><strong>Rp {{number_format($value,0,',','.')}}</strong></div>
    </div>
</section>

<div class="row g-4 mb-4">
    <div class="col-xl-8"><section class="card p-4 h-100">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2"><div><h3 class="admin-section-title">Pergerakan Stok</h3><div class="admin-section-subtitle">Jumlah unit barang masuk dan keluar per bulan (12 bulan terakhir).</div></div><a href="{{route('reports','inventory-history')}}" class="btn btn-light btn-sm">Lihat riwayat stok</a></div>
        @if($inSeries->sum()+$outSeries->sum() > 0)
            <div id="inventory-chart" class="chart-box"></div>
        @else
            <div class="chart-box d-flex flex-column justify-content-center align-items-center text-center text-secondary"><i class="fa-solid fa-chart-column fs-3 mb-2"></i><span>Belum ada pergerakan stok dalam 12 bulan terakhir.</span></div>
        @endif
    </section></div>
    <div class="col-xl-4"><section class="card p-4 h-100">
        <div class="d-flex justify-content-between align-items-start gap-2"><div><h3 class="admin-section-title">Perlu Restok</h3><div class="admin-section-subtitle">Produk yang menyentuh batas minimum.</div></div><span class="badge rounded-pill {{$lowCount?'text-bg-danger':'badge-soft'}}">{{$lowCount}}</span></div>
        <div class="mt-2">
            @forelse($low as $product)
                <div class="stock-alert-item d-flex justify-content-between align-items-start gap-2"><div><div class="stock-alert-name">{{$product->name}}</div><div class="stock-alert-detail">Batas minimum {{number_format($product->minimum_stock,0,',','.')}} {{$product->unit}}</div></div><span class="stock-alert-qty">{{number_format($product->current_stock,0,',','.')}} {{$product->unit}}</span></div>
            @empty
                <div class="text-center py-5"><i class="fa-solid fa-circle-check text-success fs-2"></i><p class="text-secondary small mt-2 mb-0">Semua produk berada di atas batas minimum.</p></div>
            @endforelse
        </div>
        @if($lowCount > 6)<a href="{{route('products.index')}}" class="small text-decoration-none mt-2">Lihat {{number_format($lowCount-6,0,',','.')}} produk lainnya</a>@endif
    </section></div>
</div>

<section class="card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-4 border-bottom"><div><h3 class="admin-section-title">Aktivitas Terbaru</h3><div class="admin-section-subtitle">Barang masuk dan keluar yang terakhir dicatat.</div></div><a href="{{route('stock.history')}}" class="btn btn-outline-primary btn-sm">Lihat riwayat stok</a></div>
    <div class="table-responsive"><table class="table activity-table"><thead><tr><th>Tanggal</th><th>Aktivitas</th><th>No. Referensi</th><th>Dicatat Oleh</th><th>Jumlah</th></tr></thead><tbody>
        @forelse($recent as $transaction)
            <tr><td class="text-nowrap">{{$transaction->transaction_date->format('d/m/Y')}}</td><td><span class="badge {{$transaction->type==='in'?'bg-success-subtle text-success':'bg-warning-subtle text-warning-emphasis'}}">{{$transaction->type==='in'?'Barang masuk':'Barang keluar'}}</span></td><td>{{$transaction->reference_no ?: 'TR-'.$transaction->id}}</td><td>{{$transaction->user?->name ?? 'Sistem'}}</td><td>{{number_format($transaction->items->sum('qty'),0,',','.')}} unit</td></tr>
        @empty<tr><td colspan="5" class="text-center text-secondary py-5">Belum ada aktivitas transaksi.</td></tr>@endforelse
    </tbody></table></div>
</section>
@endsection
@push('scripts')
@if($inSeries->sum()+$outSeries->sum() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
new ApexCharts(document.querySelector('#inventory-chart'),{
    chart:{type:'bar',height:270,toolbar:{show:false},fontFamily:'DM Sans',stacked:false},
    series:[{name:'Barang masuk',data:@json($inSeries)},{name:'Barang keluar',data:@json($outSeries)}],
    colors:['#14a38b','#f59e0b'],dataLabels:{enabled:false},plotOptions:{bar:{columnWidth:'48%',borderRadius:4}},
    grid:{borderColor:'#edf0f5',padding:{left:8,right:8}},
    xaxis:{categories:@json($months->map(fn($month)=>$month->translatedFormat('M y'))->values()),axisBorder:{show:false},axisTicks:{show:false}},
    yaxis:{min:0,tickAmount:4,labels:{formatter:value=>Math.round(value)}},
    tooltip:{y:{formatter:value=>`${value} unit`}},legend:{position:'top',horizontalAlign:'right'},
    noData:{text:'Belum ada pergerakan stok dalam periode ini.'}
}).render();
</script>
@endif
@endpush
