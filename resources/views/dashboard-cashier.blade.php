@extends('layouts.app')
@section('title','Dashboard Kasir')
@section('content')
<section class="welcome mb-4" style="background:linear-gradient(110deg,#4930b8,#6d4aff 58%,#925be3);border-radius:17px;color:white;padding:25px 28px">
    <span class="badge bg-white bg-opacity-25 fw-normal mb-2">PENJUALAN HARI INI</span>
    <h4 class="fw-bold mb-1">Halo, {{strtok(auth()->user()->name,' ')}} 👋</h4>
    <p class="mb-3 opacity-75">Catat penjualan dan pantau transaksi yang Anda lakukan.</p>
    <a href="{{route('stock.outbound')}}" class="btn btn-light text-primary fw-bold">Input Penjualan <i class="fa-solid fa-plus ms-1"></i></a>
</section>
<div class="row g-3 mb-4">
    <div class="col-md-6"><div class="card p-4 h-100"><small class="text-secondary">Transaksi hari ini</small><div class="fs-2 fw-bold mt-2">{{$todaySales}}</div></div></div>
    <div class="col-md-6"><div class="card p-4 h-100"><small class="text-secondary">Penjualan bersih hari ini</small><div class="fs-2 fw-bold mt-2">Rp {{number_format($todayRevenue,0,',','.')}}</div><small class="text-secondary">Omzet dikurangi retur yang diproses akun Anda.</small></div></div>
</div>
<div class="card"><div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-4 border-bottom"><div><h6 class="fw-bold mb-1">Penjualan Terakhir Saya</h6><small class="text-secondary">Transaksi yang dicatat oleh akun Anda.</small></div><a href="{{route('sales.history')}}" class="btn btn-outline-primary btn-sm">Lihat Riwayat Penjualan</a></div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Waktu</th><th>No. Transaksi</th><th>Barang</th><th>Qty</th><th>Total</th></tr></thead><tbody>
        @forelse($recentSales as $sale)
            @foreach($sale->items as $item)
                <tr><td>{{$sale->transaction_date->format('d/m/Y')}}<small class="d-block text-secondary">{{$sale->created_at->format('H:i')}}</small></td><td>{{$sale->reference_no ?: 'PJ-' . str_pad((string)$sale->id,6,'0',STR_PAD_LEFT)}}</td><td class="fw-semibold">{{$item->product?->name ?? 'Produk dihapus'}}</td><td>{{$item->qty}} {{$item->product?->unit}}</td><td class="fw-bold">Rp {{number_format($item->qty * $item->price,0,',','.')}}</td></tr>
            @endforeach
        @empty
            <tr><td colspan="5" class="text-center py-5 text-secondary">Belum ada penjualan yang Anda catat.</td></tr>
        @endforelse
    </tbody></table></div>
</div>
@endsection
