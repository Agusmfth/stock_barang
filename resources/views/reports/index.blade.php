@extends('layouts.app')
@section('title','Laporan '.ucfirst($type))
@section('content')
<div class="card"><div class="card-body p-4">
 <div class="d-flex justify-content-between align-items-center mb-4"><div class="btn-group"><a class="btn btn-outline-primary btn-sm {{ $type==='inventory'?'active':'' }}" href="{{route('reports','inventory')}}">Persediaan</a><a class="btn btn-outline-primary btn-sm {{ $type==='inbound'?'active':'' }}" href="{{route('reports','inbound')}}">Barang Masuk</a><a class="btn btn-outline-primary btn-sm {{ $type==='outbound'?'active':'' }}" href="{{route('reports','outbound')}}">Barang Keluar</a></div><button onclick="window.print()" class="btn btn-dark btn-sm"><i class="fa-solid fa-print me-1"></i>Cetak</button></div>
 <div class="table-responsive"><table class="table align-middle"><thead><tr>@if($type==='inventory')<th>Kode</th><th>Barang</th><th>Supplier</th><th>Gudang</th><th>Stok</th><th>Nilai Persediaan</th>@else<th>Tanggal</th><th>Referensi</th><th>Partner</th><th>Barang</th><th>Qty</th>@endif</tr></thead><tbody>
 @if($rows->isEmpty())<tr><td colspan="6" class="text-center text-secondary py-5">Tidak ada data untuk laporan ini.</td></tr>
 @elseif($type==='inventory')@foreach($rows as $r)<tr><td>{{$r->code}}</td><td class="fw-semibold">{{$r->name}}</td><td>{{$r->supplier?->name??'-'}}</td><td>{{$r->warehouse?->name}}</td><td>{{$r->current_stock}} {{$r->unit}}</td><td>Rp {{number_format($r->current_stock*$r->cost_price,0,',','.')}}</td></tr>@endforeach
 @else @foreach($rows as $r) @foreach($r->items as $i)<tr><td>{{$r->transaction_date->format('d M Y')}}</td><td>{{$r->reference_no??'-'}}</td><td>{{$r->supplier?->name??$r->salesperson?->name??'-'}}</td><td>{{$i->product->name}}</td><td>{{$i->qty}}</td></tr>@endforeach @endforeach
 @endif
 </tbody></table></div>
</div></div>
@endsection
