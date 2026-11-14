@extends('layouts.app')
@section('title','Riwayat Penjualan')
@section('content')
<div class="card"><div class="card-body p-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-4 border-bottom">
        <div><div class="section-card-title">Riwayat Penjualan</div><small class="text-secondary">@if(auth()->user()->hasRole('Kasir'))Penjualan yang dicatat oleh akun Anda.@elseSemua penjualan beserta kasir yang mencatatnya.@endif</small></div>
        <a class="btn btn-primary" href="{{route('stock.outbound')}}"><i class="fa-solid fa-plus me-2"></i>Input Penjualan</a>
    </div>
    <div class="table-responsive"><table class="table align-middle">
        <thead><tr><th>Waktu</th><th>No. Transaksi</th><th>Barang</th><th>Qty</th><th>Harga</th><th>Total</th>@if(!auth()->user()->hasRole('Kasir'))<th>Kasir</th>@endif<th>Pelanggan</th></tr></thead>
        <tbody>
        @forelse($sales as $sale)
            @php($total = $sale->items->sum(fn($item) => $item->qty * $item->price))
            @foreach($sale->items as $item)
                <tr>
                    <td><span class="fw-semibold">{{$sale->transaction_date->format('d/m/Y')}}</span><small class="d-block text-secondary">{{$sale->created_at->format('H:i')}}</small></td>
                    <td>{{ $sale->reference_no ?: 'PJ-' . str_pad((string)$sale->id, 6, '0', STR_PAD_LEFT) }}</td>
                    <td class="fw-semibold">{{$item->product?->name ?? 'Produk dihapus'}}</td>
                    <td>{{$item->qty}} {{$item->product?->unit}}</td>
                    <td>Rp {{number_format($item->price,0,',','.')}}</td>
                    <td class="fw-bold">Rp {{number_format($item->qty * $item->price,0,',','.')}}</td>
                    @if(!auth()->user()->hasRole('Kasir'))<td>{{$sale->user?->name ?? 'User dihapus'}}</td>@endif
                    <td>{{$sale->destination ?: 'Pelanggan umum'}}</td>
                </tr>
            @endforeach
        @empty
            <tr><td colspan="{{auth()->user()->hasRole('Kasir')?7:8}}" class="text-center py-5 text-secondary"><i class="fa-solid fa-receipt fs-3 d-block mb-2"></i>Belum ada penjualan yang tercatat.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="p-4">{{$sales->links()}}</div>
</div></div>
@endsection
