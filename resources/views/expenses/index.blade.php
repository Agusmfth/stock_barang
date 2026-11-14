@extends('layouts.app')
@section('title','Pengeluaran')
@section('content')
<div class="card"><div class="card-body p-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-4 border-bottom">
        <div><div class="section-card-title">Pengeluaran Operasional</div><small class="text-secondary">Catat biaya usaha untuk menghitung laba bersih pada laporan keuntungan.</small></div>
        <a class="btn btn-primary" href="{{route('expenses.create')}}"><i class="fa-solid fa-plus me-2"></i>Catat Pengeluaran</a>
    </div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Tanggal</th><th>Keterangan</th><th>Dicatat Oleh</th><th class="text-end">Jumlah Pengurang</th><th class="text-end">Aksi</th></tr></thead><tbody>
        @forelse($expenses as $expense)
            <tr>
                <td class="text-nowrap">{{$expense->expense_date->format('d/m/Y')}}</td>
                <td><span class="fw-semibold">{{$expense->description}}</span></td>
                <td>{{$expense->user?->name ?? 'User dihapus'}}</td>
                <td class="text-end fw-bold text-danger text-nowrap">Rp {{number_format($expense->amount,0,',','.')}}</td>
                <td class="text-end text-nowrap"><a class="icon-button" title="Ubah" href="{{route('expenses.edit',$expense)}}"><i class="fa-regular fa-pen-to-square"></i></a> <form class="d-inline" method="post" action="{{route('expenses.destroy',$expense)}}" data-confirm-action data-confirm-tone="danger" data-confirm-title="Hapus pengeluaran" data-confirm-message="Catatan ini akan dihapus dan tidak lagi mengurangi laba bersih.">@csrf @method('delete')<button class="icon-button text-danger" title="Hapus"><i class="fa-regular fa-trash-can"></i></button></form></td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center py-5 text-secondary"><i class="fa-solid fa-wallet fs-3 d-block mb-2"></i>Belum ada pengeluaran yang dicatat.</td></tr>
        @endforelse
    </tbody></table></div>
    @include('partials.table-pagination',['paginator'=>$expenses,'perPage'=>$perPage])
</div></div>
@endsection
