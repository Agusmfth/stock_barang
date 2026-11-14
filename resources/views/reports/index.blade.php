@extends('layouts.app')
@section('title',match($type){'inventory'=>'Laporan Persediaan','inventory-history'=>'Riwayat Stok','inbound'=>'Laporan Barang Masuk','outbound'=>'Laporan Barang Keluar'})
@section('content')
@php
    $reportTitles = ['inventory'=>'Persediaan Saat Ini','inventory-history'=>'Riwayat Stok','inbound'=>'Barang Masuk','outbound'=>'Barang Keluar'];
    $reportDescriptions = ['inventory'=>'Posisi stok produk yang tersimpan saat ini.','inventory-history'=>'Pergerakan stok berdasarkan rentang tanggal pilihan.','inbound'=>'Barang yang diterima pada rentang tanggal pilihan.','outbound'=>'Barang yang keluar atau terjual pada rentang tanggal pilihan.'];
    $pdfParams = array_merge(['type'=>$type,'format'=>'pdf'], request()->query());
    $excelParams = array_merge(['type'=>$type,'format'=>'excel'], request()->query());
@endphp
<style>
    .op-report-head{padding:22px 24px;border-bottom:1px solid #efedf5}
    .op-report-head h2{font:800 17px 'Plus Jakarta Sans';margin:0 0 4px;color:#292742}.op-report-head p{margin:0;color:#858199;font-size:12px}
    .op-tabs{display:flex;flex-wrap:wrap;gap:7px}.op-tabs .btn{white-space:nowrap}
    .op-filter-grid{display:grid;grid-template-columns:minmax(170px,1fr) minmax(190px,1fr) minmax(190px,1fr) 190px;gap:14px;align-items:start}
    .op-filter-field{min-width:0;display:grid;grid-template-rows:18px 42px 16px;row-gap:7px}.op-filter-field .form-label{height:18px;line-height:18px;margin:0}.op-filter-field .form-control,.op-filter-field .form-select{height:42px;min-height:42px}
    .op-filter-help{font-size:11px;line-height:16px;color:#858199;white-space:nowrap}
    .op-filter-button{display:grid;grid-template-rows:18px 42px 16px;row-gap:7px}.op-filter-button .btn{grid-row:2;height:42px;margin:0}
    .op-range{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:11px 14px;background:#f7f6fb;border:1px solid #efedf5;border-radius:10px}
    .op-range span{font-size:12px;color:#858199}.op-range strong{font:700 13px 'Plus Jakarta Sans';color:#4e37ae}
    .op-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px;margin-bottom:18px}
    .op-metric{background:#fff;border:1px solid #efedf5;border-radius:13px;padding:16px}.op-metric small{color:#858199;font-weight:600}.op-metric strong{display:block;font:800 18px 'Plus Jakarta Sans';margin-top:7px;color:#292742}.op-metric.highlight{background:#f2efff;border-color:#e3dcff}.op-metric.highlight strong{color:#5234c8}
    .op-table-wrap{overflow-x:auto}.op-data-table{width:100%;min-width:860px;table-layout:fixed;border-collapse:collapse}.op-data-table th,.op-data-table td{vertical-align:middle}.op-data-table th{padding:12px 14px!important}.op-data-table td{padding:13px 14px!important;overflow-wrap:anywhere}.op-data-table .num{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}.op-data-table .text-cell{white-space:normal}.op-data-table tbody tr:nth-child(even){background:#fcfbfe}
    .op-data-table.inventory col:nth-child(1){width:14%}.op-data-table.inventory col:nth-child(2){width:24%}.op-data-table.inventory col:nth-child(3){width:18%}.op-data-table.inventory col:nth-child(4){width:12%}.op-data-table.inventory col:nth-child(5){width:22%}.op-data-table.inventory col:nth-child(6){width:10%}
    .op-data-table.inventory-history col:nth-child(1){width:25%}.op-data-table.inventory-history col:nth-child(2){width:16%}.op-data-table.inventory-history col:nth-child(3){width:16%}.op-data-table.inventory-history col:nth-child(4){width:16%}.op-data-table.inventory-history col:nth-child(5){width:27%}
    .op-data-table.inbound col:nth-child(1),.op-data-table.outbound col:nth-child(1){width:12%}.op-data-table.inbound col:nth-child(2),.op-data-table.outbound col:nth-child(2){width:15%}.op-data-table.inbound col:nth-child(3),.op-data-table.outbound col:nth-child(3){width:21%}.op-data-table.inbound col:nth-child(4),.op-data-table.outbound col:nth-child(4){width:11%}.op-data-table.inbound col:nth-child(5),.op-data-table.outbound col:nth-child(5){width:15%}.op-data-table.inbound col:nth-child(6),.op-data-table.outbound col:nth-child(6){width:15%}.op-data-table.inbound col:nth-child(7),.op-data-table.outbound col:nth-child(7){width:11%}
    .op-data-table.inventory :is(th,td):nth-child(4),.op-data-table.inventory :is(th,td):nth-child(5),.op-data-table.inventory-history :is(th,td):nth-child(n+2),.op-data-table.inbound :is(th,td):nth-child(4),.op-data-table.outbound :is(th,td):nth-child(4),.op-data-table.inbound :is(th,td):nth-child(5),.op-data-table.outbound :is(th,td):nth-child(5),.op-data-table.inbound :is(th,td):nth-child(6),.op-data-table.outbound :is(th,td):nth-child(6){text-align:right;font-variant-numeric:tabular-nums}
    @media(max-width:1000px){.op-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.op-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:600px){.op-filter-grid{grid-template-columns:1fr}.op-summary{grid-template-columns:1fr 1fr}.op-filter-help{white-space:normal}.op-range{align-items:flex-start;flex-direction:column}}
</style>

<div class="card mb-4">
    <div class="op-report-head d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div><h2>{{$reportTitles[$type]}}</h2><p>{{$reportDescriptions[$type]}}</p></div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="op-tabs">
                <a class="btn btn-sm {{$type==='inventory'?'btn-primary':'btn-outline-primary'}}" href="{{route('reports','inventory')}}">Persediaan</a>
                <a class="btn btn-sm {{$type==='inventory-history'?'btn-primary':'btn-outline-primary'}}" href="{{route('reports','inventory-history')}}">Riwayat Stok</a>
                <a class="btn btn-sm {{$type==='inbound'?'btn-primary':'btn-outline-primary'}}" href="{{route('reports','inbound')}}">Barang Masuk</a>
                <a class="btn btn-sm {{$type==='outbound'?'btn-primary':'btn-outline-primary'}}" href="{{route('reports','outbound')}}">Barang Keluar</a>
            </div>
            <a class="btn btn-outline-danger btn-sm" href="{{route('reports.export',$pdfParams)}}"><i class="fa-regular fa-file-pdf me-1"></i>PDF</a>
            <a class="btn btn-outline-success btn-sm" href="{{route('reports.export',$excelParams)}}"><i class="fa-regular fa-file-excel me-1"></i>Excel</a>
        </div>
    </div>

    @if($type!=='inventory')
    <div class="p-4 border-bottom">
        <form method="get" action="{{route('reports',$type)}}" id="operationalFilter">
            <div class="op-filter-grid">
                <div class="op-filter-field"><label class="form-label" for="opPeriod">Periode</label><select class="form-select" id="opPeriod" name="period"><option value="daily" @selected($period==='daily')>Harian</option><option value="weekly" @selected($period==='weekly')>Mingguan</option><option value="monthly" @selected($period==='monthly')>Bulanan</option></select><small class="op-filter-help">Pilih periode laporan.</small></div>
                <div class="op-filter-field" id="opDateField" style="{{$period==='daily'?'':'display:none'}}"><label class="form-label" for="opDate">Tanggal</label><input class="form-control" type="date" id="opDate" name="date" value="{{$selectedDate}}"><small class="op-filter-help">Laporan satu tanggal.</small></div>
                <div class="op-filter-field" id="opFromField" style="{{$period==='weekly'?'':'display:none'}}"><label class="form-label" for="opFrom">Dari tanggal</label><input class="form-control" type="date" id="opFrom" name="date_from" value="{{$selectedFrom}}"><small class="op-filter-help">Tanggal awal.</small></div>
                <div class="op-filter-field" id="opToField" style="{{$period==='weekly'?'':'display:none'}}"><label class="form-label" for="opTo">Sampai tanggal</label><input class="form-control" type="date" id="opTo" name="date_to" value="{{$selectedTo}}"><small class="op-filter-help" id="opRangeHelp">Maksimal 7 hari.</small></div>
                <div class="op-filter-field" id="opMonthField" style="{{$period==='monthly'?'':'display:none'}}"><label class="form-label" for="opMonth">Bulan</label><input class="form-control" type="month" id="opMonth" name="month" value="{{$selectedMonth}}"><small class="op-filter-help">Pilih bulan laporan.</small></div>
                <div class="op-filter-button"><button class="btn btn-primary w-100" id="opSubmit"><i class="fa-solid fa-filter me-2"></i>Tampilkan</button></div>
            </div>
            <div class="op-range mt-3"><span><i class="fa-regular fa-calendar me-2"></i>Rentang laporan</span><strong id="opRangeLabel">{{$periodLabel}}</strong></div>
        </form>
    </div>
    @else
        <div class="px-4 pt-3"><div class="alert alert-light border mb-0"><i class="fa-solid fa-circle-info me-2 text-primary"></i>Ini adalah snapshot stok saat ini. Untuk melihat data berdasarkan tanggal, pilih Riwayat Stok, Barang Masuk, atau Barang Keluar.</div></div>
    @endif

    @if($type==='inventory')
        <div class="op-summary p-4 pb-0"><div class="op-metric"><small>Jenis Produk</small><strong>{{$totalProducts}}</strong></div><div class="op-metric"><small>Total Unit Stok</small><strong>{{number_format($totalUnits,0,',','.')}}</strong></div><div class="op-metric highlight"><small>Nilai Persediaan</small><strong>Rp {{number_format($totalValue,0,',','.')}}</strong></div></div>
    @elseif($type==='inventory-history')
        <div class="op-summary p-4 pb-0"><div class="op-metric"><small>Barang Masuk</small><strong class="text-success">+{{number_format($totalInbound,0,',','.')}} unit</strong></div><div class="op-metric"><small>Barang Keluar</small><strong class="text-danger">−{{number_format($totalOutbound,0,',','.')}} unit</strong></div><div class="op-metric"><small>Sisa Stok Akhir</small><strong>{{number_format($totalUnits,0,',','.')}} unit</strong></div><div class="op-metric highlight"><small>Nilai Persediaan Akhir</small><strong>Rp {{number_format($totalValue,0,',','.')}}</strong></div></div>
    @else
        <div class="op-summary p-4 pb-0"><div class="op-metric"><small>Jumlah Transaksi</small><strong>{{$totalTransactions}}</strong></div><div class="op-metric"><small>Total Unit</small><strong>{{number_format($totalUnits,0,',','.')}}</strong></div><div class="op-metric highlight"><small>{{$type==='inbound'?'Total Nilai Barang Masuk':'Total Nilai Barang Keluar'}}</small><strong>Rp {{number_format($totalAmount,0,',','.')}}</strong></div></div>
    @endif

    <div class="table-responsive op-table-wrap"><table class="table align-middle mb-0 op-data-table {{$type}}"><colgroup>@if($type==='inventory')<col><col><col><col><col><col>@elseif($type==='inventory-history')<col><col><col><col><col>@else<col><col><col><col><col><col><col>@endif</colgroup><thead><tr>
        @if($type==='inventory')<th>Kode</th><th>Barang</th><th>Kategori</th><th>Stok</th><th>Nilai Persediaan</th><th></th>
        @elseif($type==='inventory-history')<th>Tanggal</th><th>Total Unit</th><th>Barang Masuk</th><th>Barang Keluar</th><th>Nilai Persediaan</th>
        @else<th>Tanggal</th><th>Referensi</th><th>Barang</th><th>Qty</th><th>Harga / Unit</th><th>Subtotal</th><th>Dicatat Oleh</th>@endif
    </tr></thead><tbody>
    @if($type==='inventory' && $rows->isEmpty())<tr><td colspan="6" class="text-center text-secondary py-5">Belum ada produk.</td></tr>
    @elseif($type==='inventory-history' && !$rows)<tr><td colspan="5" class="text-center text-secondary py-5">Tidak ada data pada rentang ini.</td></tr>
    @elseif(in_array($type,['inbound','outbound'],true) && $rows->isEmpty())<tr><td colspan="7" class="text-center text-secondary py-5">Tidak ada transaksi pada rentang ini.</td></tr>
    @elseif($type==='inventory')
        @foreach($rows as $row)<tr><td>{{$row->code}}</td><td><a class="table-title" href="{{route('products.show',$row)}}">{{$row->name}}</a></td><td>{{$row->category?:'-'}}</td><td><span class="badge rounded-pill badge-soft">{{$row->current_stock}} {{$row->unit}}</span></td><td>Rp {{number_format($row->current_stock*$row->cost_price,0,',','.')}}</td><td><a class="icon-button" title="Lihat detail" href="{{route('products.show',$row)}}"><i class="fa-regular fa-eye"></i></a></td></tr>@endforeach
    @elseif($type==='inventory-history')
        @foreach($rows as $row)<tr><td class="fw-semibold">{{$row['date']->translatedFormat('d F Y')}}</td><td>{{$row['units']}} unit</td><td class="text-success fw-semibold">+{{$row['inbound']}}</td><td class="text-danger fw-semibold">−{{$row['outbound']}}</td><td>Rp {{number_format($row['value'],0,',','.')}}</td></tr>@endforeach
    @else
        @foreach($rows as $transaction)@foreach($transaction->items as $item)<tr><td>{{$transaction->transaction_date->format('d/m/Y')}}</td><td>{{$transaction->reference_no?:'TR-'.$transaction->id}}</td><td>{{$item->product?->name??'Produk dihapus'}}</td><td>{{$item->qty}} {{$item->product?->unit}}</td><td>Rp {{number_format($item->price,0,',','.')}}</td><td class="fw-semibold">Rp {{number_format($item->qty*$item->price,0,',','.')}}</td><td>{{$transaction->user?->name??'-'}}</td></tr>@endforeach @endforeach
    @endif
    </tbody></table></div>
</div></div>

@if($type!=='inventory')
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const period=document.getElementById('opPeriod'), date=document.getElementById('opDate'), from=document.getElementById('opFrom'), to=document.getElementById('opTo'), month=document.getElementById('opMonth'), submit=document.getElementById('opSubmit'), range=document.getElementById('opRangeLabel'), help=document.getElementById('opRangeHelp');
    const fmt=new Intl.DateTimeFormat('id-ID',{day:'numeric',month:'short',year:'numeric'});
    function update(){
        const p=period.value;
        document.getElementById('opDateField').style.display=p==='daily'?'':'none';
        document.getElementById('opFromField').style.display=p==='weekly'?'':'none';
        document.getElementById('opToField').style.display=p==='weekly'?'':'none';
        document.getElementById('opMonthField').style.display=p==='monthly'?'':'none';
        submit.disabled=false;
        if(p==='daily'&&date.value){const [y,m,d]=date.value.split('-').map(Number);range.textContent=fmt.format(new Date(y,m-1,d));return;}
        if(p==='weekly'&&from.value&&to.value){
            const [fy,fm,fd]=from.value.split('-').map(Number),[ty,tm,td]=to.value.split('-').map(Number);
            const a=new Date(fy,fm-1,fd),b=new Date(ty,tm-1,td),days=Math.round((b-a)/86400000),max=new Date(a);max.setDate(a.getDate()+6);
            const mm=String(max.getMonth()+1).padStart(2,'0'),dd=String(max.getDate()).padStart(2,'0');to.min=from.value;to.max=`${max.getFullYear()}-${mm}-${dd}`;
            const valid=days>=0&&days<=6;submit.disabled=!valid;help.classList.toggle('text-danger',!valid);help.textContent=valid?'Maksimal 7 hari.':'Rentang maksimal 7 hari.';range.textContent=valid?`${fmt.format(a)} – ${fmt.format(b)}`:'Periksa kembali rentang tanggal.';return;
        }
        if(p==='monthly'&&month.value){const [y,m]=month.value.split('-').map(Number);range.textContent=`${fmt.format(new Date(y,m-1,1))} – ${fmt.format(new Date(y,m,0))}`;}
    }
    [period,date,from,to,month].forEach(input=>input.addEventListener('change',update));update();
});
</script>
@endpush
@endif
@endsection
