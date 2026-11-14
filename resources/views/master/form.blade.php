@extends('layouts.app')
@section('title',($row->exists?'Ubah':'Tambah').' Produk')
@section('content')
<style>
    .product-form{max-width:1180px;margin:0 auto}.product-form .card{overflow:hidden}
    .product-form-head{padding:24px 28px;background:linear-gradient(115deg,#fff,#faf9ff);border-bottom:1px solid #efedf5}
    .product-form-head h2{font:800 19px 'Plus Jakarta Sans';margin:0 0 5px;color:#292742}.product-form-head p{margin:0;color:#858199}
    .form-section{padding:25px 28px;border-bottom:1px solid #efedf5}.form-section:last-child{border-bottom:0}
    .form-section-head{display:flex;align-items:center;gap:12px;margin-bottom:20px}.form-section-icon{width:38px;height:38px;display:grid;place-items:center;border-radius:11px;background:#f0edff;color:#6d4aff}
    .form-section-head h3{font:700 14px 'Plus Jakarta Sans';margin:0}.form-section-head small{color:#858199}
    .form-section .form-label{margin-bottom:7px}.form-section .form-control,.form-section .form-select{background-color:#fff}
    .price-preview{height:100%;min-height:75px;background:#f4f1ff;border:1px solid #e7e0ff;border-radius:11px;padding:13px 15px;display:flex;justify-content:space-between;align-items:center}
    .price-preview small{color:#6d6890}.price-preview strong{font-size:16px;color:#5539c6}
    .form-actions{padding:18px 28px;background:#fbfaff;display:flex;justify-content:space-between;align-items:center;gap:12px}
    @media(max-width:600px){.product-form-head,.form-section{padding:20px}.form-actions{padding:16px 20px;flex-wrap:wrap}.form-actions .btn{flex:1}}
</style>

@php($nextCode = 'BRG-'.str_pad((string)((\App\Models\Product::max('id') ?? 0) + 1),5,'0',STR_PAD_LEFT))
<div class="product-form">
    <form method="post" data-confirm-action data-confirm-title="Simpan produk" data-confirm-message="Pastikan informasi produk dan harga sudah benar." action="{{$row->exists?route('products.update',$row):route('products.store')}}">
        @csrf @if($row->exists)@method('put')@endif
        <div class="card">
            <div class="product-form-head d-flex justify-content-between align-items-center gap-3">
                <div><h2>{{$row->exists?'Perbarui informasi produk':'Lengkapi informasi produk'}}</h2><p>Atur identitas barang, stok, dan harga jual.</p></div>
                <span class="badge rounded-pill badge-soft"><i class="fa-solid fa-snowflake me-1"></i> Produk Frozen</span>
            </div>

            <section class="form-section">
                <div class="form-section-head"><span class="form-section-icon"><i class="fa-solid fa-tag"></i></span><div><h3>Identitas Produk</h3><small>Nama dan kode untuk mengenali produk di kasir.</small></div></div>
                <div class="row g-3">
                    <div class="col-lg-6"><label class="form-label">Nama Barang <span class="text-danger">*</span></label><input class="form-control" name="name" value="{{old('name',$row->name)}}" placeholder="Contoh: Nugget Ayam 500g" required autofocus></div>
                    <div class="col-sm-6 col-lg-3"><label class="form-label">Kode Barang <span class="text-danger">*</span></label><input class="form-control" name="code" value="{{old('code',$row->code??$nextCode)}}" required></div>
                    <div class="col-sm-6 col-lg-3"><label class="form-label">SKU <span class="text-danger">*</span></label><input class="form-control" name="sku" value="{{old('sku',$row->sku??'SKU-'.strtoupper(str()->random(6)))}}" required></div>
                    <div class="col-md-4"><label class="form-label">Kategori</label><input class="form-control" name="category" value="{{old('category',$row->category)}}" placeholder="Contoh: Nugget"></div>
                    <div class="col-md-4"><label class="form-label">Merek</label><input class="form-control" name="brand" value="{{old('brand',$row->brand)}}" placeholder="Nama merek"></div>
                    <div class="col-md-4"><label class="form-label">Satuan <span class="text-danger">*</span></label><input class="form-control" name="unit" value="{{old('unit',$row->unit??'Pcs')}}" placeholder="Pcs, pack, kg" required></div>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section-head"><span class="form-section-icon"><i class="fa-solid fa-boxes-stacked"></i></span><div><h3>Pengaturan Stok</h3><small>Isi stok awal dan batas pengingat stok rendah.</small></div></div>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Stok Saat Ini <span class="text-danger">*</span></label><input class="form-control" type="number" min="0" name="current_stock" value="{{old('current_stock',$row->current_stock??0)}}" required><small class="text-secondary">Stok awal saat produk pertama kali dibuat.</small></div>
                    <div class="col-md-4"><label class="form-label">Batas Stok Rendah <span class="text-danger">*</span></label><input class="form-control" type="number" min="0" name="minimum_stock" value="{{old('minimum_stock',$row->minimum_stock??0)}}" required><small class="text-secondary">Peringatan muncul saat stok mencapai batas ini.</small></div>
                    <div class="col-md-4"><label class="form-label">Status Produk <span class="text-danger">*</span></label><select class="form-select" name="status" required><option value="active" @selected(old('status',$row->status??'active')==='active')>Aktif — tampil di kasir</option><option value="inactive" @selected(old('status',$row->status??'active')==='inactive')>Nonaktif — disembunyikan dari kasir</option></select></div>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section-head"><span class="form-section-icon"><i class="fa-solid fa-money-bill-wave"></i></span><div><h3>Harga Produk</h3><small>Harga modal dipakai untuk menghitung laba penjualan.</small></div></div>
                <div class="row g-3 align-items-stretch">
                    <div class="col-md-4"><label class="form-label">Harga Modal <span class="text-danger">*</span></label><div class="input-group"><span class="input-group-text">Rp</span><input class="form-control" type="text" inputmode="numeric" data-money-input id="costPrice" name="cost_price" value="{{old('cost_price',$row->cost_price??0)}}" required></div></div>
                    <div class="col-md-4"><label class="form-label">Harga Jual <span class="text-danger">*</span></label><div class="input-group"><span class="input-group-text">Rp</span><input class="form-control" type="text" inputmode="numeric" data-money-input id="sellingPrice" name="selling_price" value="{{old('selling_price',$row->selling_price??0)}}" required></div></div>
                    <div class="col-md-4"><label class="form-label">Perkiraan Laba per Unit</label><div class="price-preview"><small>Harga jual − harga modal</small><strong id="unitMargin">Rp 0</strong></div></div>
                </div>
            </section>

            <div class="form-actions"><a class="btn btn-light" href="{{route('products.index')}}"><i class="fa-solid fa-arrow-left me-2"></i>Batal</a><button class="btn btn-primary px-4"><i class="fa-regular fa-floppy-disk me-2"></i>{{$row->exists?'Simpan Perubahan':'Simpan Produk'}}</button></div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const fields = [...document.querySelectorAll('[data-money-input]')];
    const cost = document.getElementById('costPrice');
    const selling = document.getElementById('sellingPrice');
    const margin = document.getElementById('unitMargin');
    const format = value => {
        const digits = String(value ?? '').replace(/\D/g, '');
        return digits ? Number(digits).toLocaleString('id-ID') : '';
    };
    const numeric = field => Number(String(field.value).replace(/\D/g, '') || 0);
    const updateMargin = () => margin.textContent = 'Rp ' + (numeric(selling) - numeric(cost)).toLocaleString('id-ID');
    fields.forEach(field => {
        field.value = format(field.value);
        field.addEventListener('input', () => { field.value = format(field.value); updateMargin(); });
    });
    updateMargin();
    document.querySelector('form').addEventListener('submit', () => fields.forEach(field => {
        field.value = field.value.replace(/\./g, '').replace(/,/g, '.');
    }));
});
</script>
@endpush
