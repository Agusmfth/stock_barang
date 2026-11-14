@extends('layouts.app')
@section('title','Riwayat Retur Penjualan')
@section('content')
<div class="card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-4 border-bottom"><div><div class="section-card-title">Riwayat Retur Penjualan</div><small class="text-secondary">Catatan barang yang dikembalikan, stok yang dipulihkan, dan nilai pengembalian.</small></div><a class="btn btn-outline-primary" href="{{route('sales.history')}}"><i class="fa-solid fa-arrow-left me-2"></i>Kembali ke Penjualan</a></div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Waktu</th><th>No. Retur</th><th>Transaksi Asal</th><th>Barang Diretur</th><th>Nilai Pengembalian</th><th>Alasan</th><th>Diproses Oleh</th></tr></thead><tbody>
        @forelse($returns as $return)
            <tr>
                <td class="text-nowrap">{{$return->returned_at->format('d/m/Y')}}<small class="d-block text-secondary">{{$return->returned_at->format('H:i')}}</small></td>
                <td class="fw-semibold">{{$return->return_no}}</td>
                <td>{{$return->sale?->reference_no ?: 'PJ-'.str_pad((string)($return->sale?->id ?? 0),6,'0',STR_PAD_LEFT)}}</td>
                <td>@foreach($return->items as $item)<div class="text-nowrap">{{$item->product?->name ?? 'Produk dihapus'}} <span class="text-secondary">× {{$item->qty}}</span></div>@endforeach</td>
                <td class="fw-bold text-danger">Rp {{number_format($return->refund_total,0,',','.')}}</td>
                <td>{{$return->reason}}</td>
                <td>{{$return->user?->name ?? 'User dihapus'}}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center py-5 text-secondary"><i class="fa-solid fa-rotate-left fs-3 d-block mb-2"></i>Belum ada retur penjualan.</td></tr>
        @endforelse
    </tbody></table></div>
    @include('partials.table-pagination',['paginator'=>$returns,'perPage'=>$perPage])
</div>
@endsection
