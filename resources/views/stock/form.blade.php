@extends('layouts.app')
@section('title',$type==='in'?'Transaksi Barang Masuk':'Transaksi Barang Keluar')
@section('content')
<form method="post" data-confirm-action data-confirm-title="Simpan transaksi" data-confirm-message="Simpan transaksi dan perbarui stok sekarang?" action="{{route('stock.'.($type==='in'?'inbound.store':'outbound.store'))}}">@csrf
<div class="card"><div class="card-body p-4"><div class="row g-3">
    <div class="col-md-4"><label class="form-label">Tanggal *</label><input class="form-control" type="date" name="transaction_date" value="{{old('transaction_date',today()->format('Y-m-d'))}}" required></div>
    <div class="col-md-4"><label class="form-label">Barang *</label><select class="form-select" id="product_id" name="product_id" required><option value="">Pilih barang</option>@foreach($products as $p)<option value="{{$p->id}}" data-price="{{$type==='in'?$p->cost_price:$p->selling_price}}" @selected(old('product_id')==$p->id)>{{$p->name}} — stok {{$p->current_stock}}</option>@endforeach</select></div>
    <div class="col-md-2"><label class="form-label">Qty *</label><input class="form-control" type="number" min="1" name="qty" value="{{old('qty')}}" required></div>
    <div class="col-md-2"><label class="form-label">{{$type==='in'?'Harga Modal / Unit':'Harga Jual / Unit'}} *</label><input class="form-control" id="price" type="text" inputmode="numeric" data-money-input name="price" value="{{old('price')}}" required><small class="text-secondary">Otomatis dari master barang</small></div>
    @if($type==='in')
    <div class="col-md-6"><label class="form-label">Supplier *</label><select class="form-select" name="supplier_id" required><option value="">Pilih supplier</option>@foreach($suppliers as $x)<option value="{{$x->id}}" @selected(old('supplier_id')==$x->id)>{{$x->name}}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">No. Invoice</label><input class="form-control" name="reference_no" value="{{old('reference_no')}}"></div>
    @else
    <div class="col-md-6"><label class="form-label">Sales *</label><select class="form-select" name="salesperson_id" required><option value="">Pilih sales</option>@foreach($salespeople as $x)<option value="{{$x->id}}" @selected(old('salesperson_id')==$x->id)>{{$x->name}}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Tujuan *</label><input class="form-control" name="destination" value="{{old('destination')}}" required></div>
    @endif
    <div class="col-12"><label class="form-label">Catatan</label><textarea class="form-control" name="notes">{{old('notes')}}</textarea></div>
</div></div><div class="card-footer bg-white border-0 p-4 pt-0"><button class="btn btn-primary px-4">Simpan Transaksi</button></div></div>
</form>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const product = document.getElementById('product_id');
    const price = document.getElementById('price');

    const formatMoney = value => {
        const raw = String(value ?? '').trim();
        if (/^\d+\.\d{2}$/.test(raw)) return Number(raw).toLocaleString('id-ID', {maximumFractionDigits: 2});
        const digits = raw.replace(/\D/g, '');
        return digits ? Number(digits).toLocaleString('id-ID') : '';
    };
    function fillProductPrice() {
        const option = product.options[product.selectedIndex];
        price.value = option && option.dataset.price !== undefined ? formatMoney(option.dataset.price) : '';
    }

    price.value = formatMoney(price.value);
    price.addEventListener('input', () => { price.value = formatMoney(price.value); });
    product.addEventListener('change', fillProductPrice);
    if (product.value && !price.value) fillProductPrice();
    product.form.addEventListener('submit', () => { price.value = price.value.replace(/\./g, '').replace(/,/g, '.'); });
});
</script>
@endpush
