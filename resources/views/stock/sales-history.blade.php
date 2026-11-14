@extends('layouts.app')
@section('title','Riwayat Penjualan')
@section('content')
<style>
    .sales-history-table{min-width:900px;table-layout:fixed}.sales-history-table th,.sales-history-table td{padding:14px 12px!important;vertical-align:middle}
    .sales-history-table:not(.admin-view) col:nth-child(1){width:12%}.sales-history-table:not(.admin-view) col:nth-child(2){width:15%}.sales-history-table:not(.admin-view) col:nth-child(3){width:25%}.sales-history-table:not(.admin-view) col:nth-child(4){width:16%}.sales-history-table:not(.admin-view) col:nth-child(5){width:17%}.sales-history-table:not(.admin-view) col:nth-child(6){width:15%}
    .sales-history-table.admin-view col:nth-child(1){width:10%}.sales-history-table.admin-view col:nth-child(2){width:13%}.sales-history-table.admin-view col:nth-child(3){width:22%}.sales-history-table.admin-view col:nth-child(4){width:13%}.sales-history-table.admin-view col:nth-child(5){width:12%}.sales-history-table.admin-view col:nth-child(6){width:15%}.sales-history-table.admin-view col:nth-child(7){width:15%}
    .sale-product-summary{font-size:12px;font-weight:700;color:#343149}.sale-product-meta{font-size:11px;color:#858199;margin-top:3px}.sale-total{font:800 14px 'Plus Jakarta Sans';white-space:nowrap;color:#292742}
    .sale-action-buttons{display:flex;flex-wrap:wrap;justify-content:center;gap:6px}.sale-details-row td{padding:0!important;border:0!important}.sale-details-box{padding:15px 20px 17px 22px;background:#faf9fd;border-top:1px solid #efedf5;border-bottom:1px solid #efedf5}.sale-details-title{font-size:11px;font-weight:700;color:#77748a;margin-bottom:8px}.sale-detail-line{display:grid;grid-template-columns:minmax(0,1.8fr) .8fr .9fr .9fr 1fr;gap:12px;align-items:center;padding:8px 0;border-top:1px solid #eeebf4;font-size:12px}.sale-detail-name{font-weight:700;overflow-wrap:anywhere}.sale-detail-muted{font-size:11px;color:#858199;margin-top:2px}.sale-detail-number{text-align:right;white-space:nowrap}.sale-return-note{font-size:10px;color:#6548d8;margin-top:3px}
    .sales-history-mobile{display:none}.mobile-sale-card{border:1px solid #eceaf4;border-radius:13px;background:#fff;padding:14px;margin:10px}.mobile-sale-top{display:flex;justify-content:space-between;align-items:flex-start;gap:9px}.mobile-sale-reference{font-size:13px;font-weight:800;color:#292742}.mobile-sale-date{font-size:11px;color:#858199;margin-top:3px}.mobile-sale-actions{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:5px}.mobile-sale-meta{display:flex;flex-wrap:wrap;gap:5px 12px;color:#77748a;font-size:11px;margin-top:10px}.mobile-sale-summary{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:11px;padding:10px 0;border-top:1px solid #efedf5;border-bottom:1px solid #efedf5}.mobile-sale-product{min-width:0}.mobile-sale-product strong{display:block;font-size:12px;color:#343149;overflow-wrap:anywhere}.mobile-sale-product small{display:block;color:#858199;font-size:11px;margin-top:3px}.mobile-sale-bottom{display:flex;justify-content:space-between;align-items:center;gap:10px;padding-top:10px}.mobile-sale-bottom small{display:block;font-size:10px;color:#858199}.mobile-sale-bottom strong{font:800 15px 'Plus Jakarta Sans';color:#292742}
    .mobile-sale-details{padding:9px 12px;background:#faf9fd;border-radius:0 0 10px 10px}.mobile-sale-details .sale-detail-line{grid-template-columns:minmax(0,1fr) auto;gap:5px 12px}.mobile-sale-details .sale-detail-values{grid-column:1/-1;display:flex;justify-content:space-between;gap:8px;color:#77748a;font-size:10px}.sales-history-empty{text-align:center;padding:34px 15px;color:#858199}
    @media(max-width:575.98px){.sales-history-toolbar{padding:16px!important;align-items:flex-start!important}.sales-history-actions{width:100%}.sales-history-actions .btn{flex:1}}
    @media(max-width:767.98px){.sales-history-desktop{display:none!important}.sales-history-mobile{display:block}.sales-history-toolbar{padding:16px!important;align-items:flex-start!important}.sales-history-actions{width:100%}.sales-history-actions .btn{flex:1}}
</style>

<div class="card"><div class="card-body p-0">
    <div class="sales-history-toolbar d-flex flex-wrap justify-content-between align-items-center gap-3 p-4 border-bottom">
        <div><div class="section-card-title">Riwayat Penjualan</div><small class="text-secondary">@if(auth()->user()->hasRole('Kasir'))Penjualan yang dicatat oleh akun Anda.@elseSemua penjualan beserta kasir yang mencatatnya.@endif</small></div>
        <div class="sales-history-actions d-flex flex-wrap gap-2"><a class="btn btn-outline-primary" href="{{route('sales.returns.history')}}"><i class="fa-solid fa-rotate-left me-2"></i>Riwayat Retur</a><a class="btn btn-primary" href="{{route('stock.outbound')}}"><i class="fa-solid fa-plus me-2"></i>Input Penjualan</a></div>
    </div>

    <div class="table-responsive sales-history-desktop"><table class="table align-middle sales-history-table {{auth()->user()->hasRole('Kasir')?'':'admin-view'}}">
        <colgroup>@if(auth()->user()->hasRole('Kasir'))<col><col><col><col><col><col>@else<col><col><col><col><col><col><col>@endif</colgroup>
        <thead><tr><th>Waktu</th><th>No. Transaksi</th><th>Barang</th><th>Total Nota</th>@if(!auth()->user()->hasRole('Kasir'))<th>Kasir</th>@endif<th>Pelanggan</th><th>Aksi</th></tr></thead>
        <tbody>
        @forelse($sales as $sale)
            @php($saleTotal = $sale->items->sum(fn($item) => $item->qty * $item->price))
            @php($canReturn = $sale->items->contains(fn($item) => $item->salesReturnItems->sum('qty') < $item->qty))
            @php($detailId = 'saleDetailsDesktop'.$sale->id)
            <tr>
                <td class="text-nowrap"><span class="fw-semibold">{{$sale->transaction_date->format('d/m/Y')}}</span><small class="d-block text-secondary">{{$sale->created_at->format('H:i')}}</small></td>
                <td class="fw-semibold">{{$sale->reference_no ?: 'PJ-'.str_pad((string)$sale->id,6,'0',STR_PAD_LEFT)}}</td>
                <td>@if($sale->items->count()===1)@php($item=$sale->items->first())<div class="sale-product-summary">{{$item->product?->name ?? 'Produk dihapus'}}</div><div class="sale-product-meta">{{$item->qty}} {{$item->product?->unit}}</div>@else<span class="badge badge-soft">{{$sale->items->count()}} jenis barang</span>@endif</td>
                <td class="sale-total">Rp {{number_format($saleTotal,0,',','.')}}</td>
                @if(!auth()->user()->hasRole('Kasir'))<td>{{$sale->user?->name ?? 'User dihapus'}}</td>@endif
                <td>{{$sale->destination ?: 'Pelanggan umum'}}</td>
                <td><div class="sale-action-buttons"><button class="btn btn-sm btn-light text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#{{$detailId}}" aria-expanded="false"><i class="fa-solid fa-list-ul me-1"></i>Detail</button></div></td>
            </tr>
                <tr class="sale-details-row"><td colspan="{{auth()->user()->hasRole('Kasir')?6:7}}"><div class="collapse" id="{{$detailId}}"><div class="sale-details-box"><div class="sale-details-title">Rincian {{$sale->items->count()}} barang dalam nota</div>
                    @foreach($sale->items as $item)
                        @php($returnedQty = $item->salesReturnItems->sum('qty'))
                        <div class="sale-detail-line"><div><div class="sale-detail-name">{{$item->product?->name ?? 'Produk dihapus'}}</div>@if($returnedQty>0)<div class="sale-return-note">Retur {{$returnedQty}} dari {{$item->qty}} {{$item->product?->unit}}</div>@endif</div><div class="sale-detail-number">{{$item->qty}} {{$item->product?->unit}}</div><div class="sale-detail-number">Rp {{number_format($item->price,0,',','.')}} / unit</div><div class="sale-detail-number">Rp {{number_format($item->qty*$item->price,0,',','.')}}</div><div class="sale-detail-number">{{$returnedQty>0?'Diretur '.$returnedQty:'Belum diretur'}}</div></div>
                    @endforeach
                    <div class="d-flex justify-content-end mt-3">@if($canReturn)<a class="btn btn-sm btn-outline-primary" href="{{route('sales.returns.create',$sale)}}"><i class="fa-solid fa-rotate-left me-1"></i>Retur barang</a>@else<span class="badge text-bg-success">Semua barang sudah diretur</span>@endif</div>
                </div></div></td></tr>
        @empty
            <tr><td colspan="{{auth()->user()->hasRole('Kasir')?6:7}}" class="text-center py-5 text-secondary"><i class="fa-solid fa-receipt fs-3 d-block mb-2"></i>Belum ada penjualan yang tercatat.</td></tr>
        @endforelse
        </tbody>
    </table></div>

    <div class="sales-history-mobile">
        @forelse($sales as $sale)
            @php($saleTotal = $sale->items->sum(fn($item) => $item->qty * $item->price))
            @php($canReturn = $sale->items->contains(fn($item) => $item->salesReturnItems->sum('qty') < $item->qty))
            @php($detailId = 'saleDetailsMobile'.$sale->id)
            <article class="mobile-sale-card">
                <div class="mobile-sale-top"><div><div class="mobile-sale-reference">{{$sale->reference_no ?: 'PJ-'.str_pad((string)$sale->id,6,'0',STR_PAD_LEFT)}}</div><div class="mobile-sale-date">{{$sale->transaction_date->format('d/m/Y')}} · {{$sale->created_at->format('H:i')}}</div></div><div class="mobile-sale-actions"><button class="btn btn-sm btn-light text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#{{$detailId}}"><i class="fa-solid fa-list-ul me-1"></i>Detail</button></div></div>
                <div class="mobile-sale-meta">@if(!auth()->user()->hasRole('Kasir'))<span><i class="fa-regular fa-user me-1"></i>{{$sale->user?->name ?? 'User dihapus'}}</span>@endif<span><i class="fa-solid fa-user-tag me-1"></i>{{$sale->destination ?: 'Pelanggan umum'}}</span></div>
                <div class="mobile-sale-summary"><div class="mobile-sale-product">@if($sale->items->count()===1)@php($item=$sale->items->first())<strong>{{$item->product?->name ?? 'Produk dihapus'}}</strong><small>{{$item->qty}} {{$item->product?->unit}} Â· Rp {{number_format($item->price,0,',','.')}} / unit</small>@else<strong>{{$sale->items->count()}} jenis barang</strong><small>Ketuk Detail untuk melihat barang</small>@endif</div><span class="badge badge-soft">{{$sale->items->sum('qty')}} unit</span></div>
                    <div class="collapse" id="{{$detailId}}"><div class="mobile-sale-details">
                        @foreach($sale->items as $item)
                            @php($returnedQty = $item->salesReturnItems->sum('qty'))
                            <div class="sale-detail-line"><div><div class="sale-detail-name">{{$item->product?->name ?? 'Produk dihapus'}}</div>@if($returnedQty>0)<div class="sale-return-note">Retur {{$returnedQty}} dari {{$item->qty}} {{$item->product?->unit}}</div>@endif</div><div class="sale-detail-values"><span>{{$item->qty}} {{$item->product?->unit}} Ã— Rp {{number_format($item->price,0,',','.')}}</span><strong>Rp {{number_format($item->qty*$item->price,0,',','.')}}</strong></div></div>
                        @endforeach
                        <div class="d-flex justify-content-end mt-2">@if($canReturn)<a class="btn btn-sm btn-outline-primary" href="{{route('sales.returns.create',$sale)}}"><i class="fa-solid fa-rotate-left me-1"></i>Retur barang</a>@else<span class="badge text-bg-success">Semua barang sudah diretur</span>@endif</div>
                    </div></div>
                <div class="mobile-sale-bottom"><div><small>Total nota</small><strong>Rp {{number_format($saleTotal,0,',','.')}}</strong></div></div>
            </article>
        @empty
            <div class="text-center py-5 text-secondary"><i class="fa-solid fa-receipt fs-3 d-block mb-2"></i>Belum ada penjualan yang tercatat.</div>
        @endforelse
    </div>
    <div class="p-4">{{$sales->links()}}</div>
</div></div>
@endsection
