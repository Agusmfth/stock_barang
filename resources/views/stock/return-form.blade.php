@extends('layouts.app')
@section('title','Retur Penjualan')
@section('content')
<style>
    .return-intro{padding:20px 22px;border-radius:13px;background:#f4f1ff;border:1px solid #e6e0ff;color:#4f3b9d}
    .return-intro p{margin:4px 0 0;font-size:12px;color:#716a91}
    .return-table{min-width:760px}.return-table th,.return-table td{vertical-align:middle;padding:12px 14px!important}
    .return-qty{max-width:120px}.return-total{font:800 20px 'Plus Jakarta Sans';color:#5335c5}
</style>

<div class="return-intro mb-4"><strong><i class="fa-solid fa-circle-info me-2"></i>Retur sebagian atau penuh</strong><p>Pilih jumlah barang yang dikembalikan. Stok akan bertambah setelah retur disimpan. Nilai pengembalian dana hanya dicatat sebagai acuan; proses uang dilakukan langsung oleh kasir.</p></div>

<form method="post" action="{{route('sales.returns.store',$sale)}}" data-confirm-action data-confirm-title="Simpan retur penjualan" data-confirm-message="Jumlah retur akan menambah stok dan dicatat pada riwayat. Pastikan barang yang dipilih benar." id="returnForm">
    @csrf
    <div class="card mb-4">
        <div class="card-header bg-white p-4"><div class="d-flex flex-wrap justify-content-between align-items-start gap-2"><div><h2 class="section-card-title mb-1">Transaksi {{$sale->reference_no ?: 'PJ-'.str_pad((string)$sale->id,6,'0',STR_PAD_LEFT)}}</h2><small class="text-secondary">Tanggal {{$sale->transaction_date->format('d/m/Y')}} · Kasir {{$sale->user?->name ?? 'User dihapus'}}</small></div><a href="{{route('sales.history')}}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left me-2"></i>Kembali</a></div></div>
        <div class="table-responsive"><table class="table return-table"><thead><tr><th>Produk</th><th>Terjual</th><th>Sudah Diretur</th><th>Sisa Bisa Diretur</th><th>Jumlah Retur</th><th>Harga Acuan</th></tr></thead><tbody>
            @foreach($sale->items as $item)
                <tr>
                    <td class="fw-semibold">{{$item->product?->name ?? 'Produk dihapus'}}<small class="d-block text-secondary">{{$item->product?->sku}}</small></td>
                    <td>{{$item->qty}} {{$item->product?->unit}}</td>
                    <td>{{number_format($item->returned_qty,0,',','.')}} {{$item->product?->unit}}</td>
                    <td class="fw-bold">{{number_format($item->remaining_qty,0,',','.')}} {{$item->product?->unit}}</td>
                    <td><input type="hidden" name="items[{{$loop->index}}][stock_transaction_item_id]" value="{{$item->id}}"><input class="form-control return-qty" type="number" name="items[{{$loop->index}}][qty]" min="0" max="{{$item->remaining_qty}}" value="{{old('items.'.$loop->index.'.qty',0)}}" @disabled($item->remaining_qty===0) aria-label="Jumlah retur {{$item->product?->name}}"></td>
                    <td>Rp {{number_format($item->price,0,',','.')}} / unit</td>
                </tr>
            @endforeach
        </tbody></table></div>
        <div class="p-4 border-top d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div class="flex-grow-1"><label class="form-label" for="reason">Alasan retur <span class="text-danger">*</span></label><textarea class="form-control" id="reason" name="reason" rows="2" maxlength="500" required placeholder="Contoh: Pembeli salah memilih produk">{{old('reason')}}</textarea></div>
            <div class="text-end"><small class="d-block text-secondary mb-1">Perkiraan pengembalian dana</small><strong class="return-total" id="refundTotal">Rp 0</strong></div>
        </div>
        <div class="card-footer bg-white border-0 px-4 pb-4 d-flex flex-wrap justify-content-end gap-2"><a class="btn btn-light" href="{{route('sales.history')}}">Batal</a><button class="btn btn-primary" type="submit"><i class="fa-solid fa-rotate-left me-2"></i>Simpan Retur</button></div>
    </div>
</form>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const inputs = [...document.querySelectorAll('.return-qty')];
    const prices = @json($sale->items->map(fn($item) => (float) $item->price)->values());
    const total = document.getElementById('refundTotal');
    const updateTotal = () => {
        const amount = inputs.reduce((sum, input, index) => sum + Math.min(Number(input.max), Math.max(0, Number(input.value || 0))) * prices[index], 0);
        total.textContent = 'Rp ' + Math.round(amount).toLocaleString('id-ID');
    };
    inputs.forEach(input => input.addEventListener('input', updateTotal));
    updateTotal();
});
</script>
@endpush
