@extends('layouts.app')
@section('title','Master '.$title)
@section('content')
<style>@media(max-width:575.98px){.master-toolbar{padding:16px!important;align-items:stretch!important}.master-tools{width:100%;flex-direction:column}.master-search{width:100%}.master-search .form-control{min-width:0}.master-tools>.btn{width:100%;justify-content:center}.master-table th,.master-table td{padding:11px 10px!important}}</style>
<div class="card"><div class="card-body p-0"><div class="master-toolbar d-flex flex-wrap gap-3 justify-content-between align-items-center p-4 border-bottom"><div><div class="section-card-title">Daftar {{$title}}</div><small class="text-secondary">Kelola dan lihat informasi {{$title}} secara lengkap.</small></div><div class="master-tools d-flex flex-wrap gap-2"><form class="master-search d-flex gap-2"><input class="form-control" name="q" value="{{request('q')}}" placeholder="Cari {{$title}}..."><button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button></form><a class="btn btn-primary d-flex align-items-center" href="{{route($resource.'.create')}}"><i class="fa-solid fa-plus me-2"></i>Tambah {{$title}}</a></div></div>
<div class="table-responsive"><table class="table align-middle master-table"><thead><tr><th>Nama</th><th>Kode / SKU</th><th>Kategori</th><th>Stok</th><th class="text-end">Aksi</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td><a class="table-title" href="{{route($resource.'.show',$row)}}">{{$row->name}}</a><small class="d-block text-secondary">#{{str_pad($row->id,4,'0',STR_PAD_LEFT)}}</small></td><td>{{$row->code}}<small class="d-block text-secondary">{{$row->sku}}</small></td><td>{{$row->category?:'-'}}</td><td><span class="badge rounded-pill {{$row->current_stock<=$row->minimum_stock?'text-bg-danger':'badge-soft'}}">{{$row->current_stock}} {{$row->unit}}</span></td><td class="text-end text-nowrap"><a class="icon-button" title="Lihat detail" href="{{route($resource.'.show',$row)}}"><i class="fa-regular fa-eye"></i></a> <button class="icon-button" type="button" title="Ubah" data-bs-toggle="modal" data-bs-target="#editProduct{{$row->id}}"><i class="fa-regular fa-pen-to-square"></i></button> <form class="d-inline" method="post" data-confirm-action data-confirm-tone="danger" data-confirm-title="Arsipkan data" data-confirm-message="Data ini akan diarsipkan dan tidak dihitung sebagai data aktif." action="{{route($resource.'.destroy',$row)}}">@csrf @method('delete')<button class="icon-button text-danger" title="Arsipkan"><i class="fa-regular fa-trash-can"></i></button></form></td></tr>
@empty<tr><td colspan="5" class="text-center py-5 text-secondary"><i class="fa-regular fa-folder-open fs-3 d-block mb-2"></i>Belum ada data.</td></tr>@endforelse
</tbody></table></div><div class="p-4">{{$rows->links()}}</div></div></div>

@if($resource==='products')
@foreach($rows as $row)
@php($useOld = (string) old('_editing_id') === (string) $row->id)
<div class="modal fade" id="editProduct{{$row->id}}" tabindex="-1" aria-hidden="true">
 <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content border-0" style="border-radius:16px">
  <form method="post" action="{{route('products.update',$row)}}">@csrf @method('put')
   <input type="hidden" name="_editing_id" value="{{$row->id}}">
   <div class="modal-header border-0 px-4 pt-4"><div><h5 class="modal-title fw-bold">Update Produk</h5><small class="text-secondary">Perbarui informasi {{$row->name}}.</small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
   <div class="modal-body px-4"><div class="row g-3">
    <div class="col-md-6"><label class="form-label">Nama Barang *</label><input class="form-control" name="name" value="{{$useOld?old('name'):$row->name}}" required></div>
    <div class="col-md-3"><label class="form-label">Kode Barang *</label><input class="form-control" name="code" value="{{$useOld?old('code'):$row->code}}" required></div>
    <div class="col-md-3"><label class="form-label">SKU *</label><input class="form-control" name="sku" value="{{$useOld?old('sku'):$row->sku}}" required></div>
    <div class="col-md-4"><label class="form-label">Kategori</label><input class="form-control" name="category" value="{{$useOld?old('category'):$row->category}}"></div>
    <div class="col-md-4"><label class="form-label">Merk</label><input class="form-control" name="brand" value="{{$useOld?old('brand'):$row->brand}}"></div>
    <div class="col-md-4"><label class="form-label">Satuan *</label><input class="form-control" name="unit" value="{{$useOld?old('unit'):$row->unit}}" required></div>
    <div class="col-md-3"><label class="form-label">Stok Saat Ini *</label><input class="form-control" type="number" min="0" name="current_stock" value="{{$useOld?old('current_stock'):$row->current_stock}}" required></div>
    <div class="col-md-3"><label class="form-label">Min. Stok *</label><input class="form-control" type="number" min="0" name="minimum_stock" value="{{$useOld?old('minimum_stock'):$row->minimum_stock}}" required></div>
    <div class="col-md-3"><label class="form-label">Harga Modal *</label><input class="form-control" type="text" inputmode="numeric" data-modal-money name="cost_price" value="{{number_format((float)($useOld?old('cost_price'):$row->cost_price),0,',','.')}}" required></div>
    <div class="col-md-3"><label class="form-label">Harga Jual *</label><input class="form-control" type="text" inputmode="numeric" data-modal-money name="selling_price" value="{{number_format((float)($useOld?old('selling_price'):$row->selling_price),0,',','.')}}" required></div>
    <div class="col-md-4"><label class="form-label">Status *</label><select class="form-select" name="status" required><option value="active" @selected(($useOld?old('status'):$row->status)==='active')>Aktif</option><option value="inactive" @selected(($useOld?old('status'):$row->status)==='inactive')>Nonaktif</option></select></div>
   </div></div>
   <div class="modal-footer border-0 px-4 pb-4"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary px-4">Simpan Perubahan</button></div>
  </form>
 </div></div>
</div>
@endforeach
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){
 const formatMoney=value=>{const digits=String(value??'').replace(/\D/g,'');return digits?Number(digits).toLocaleString('id-ID'):''};
 document.querySelectorAll('[data-modal-money]').forEach(field=>field.addEventListener('input',()=>field.value=formatMoney(field.value)));
 @if($errors->any() && old('_editing_id')) bootstrap.Modal.getOrCreateInstance(document.getElementById('editProduct{{old('_editing_id')}}')).show(); @endif
});
</script>
@endpush
@else
@foreach($rows as $row)
@php($useOld = (string) old('_editing_id') === (string) $row->id)
<div class="modal fade" id="editRecord{{$row->id}}" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content border-0" style="border-radius:16px">
 <form method="post" action="{{route($resource.'.update',$row)}}">@csrf @method('put')<input type="hidden" name="_editing_id" value="{{$row->id}}">
 <div class="modal-header border-0 px-4 pt-4"><div><h5 class="modal-title fw-bold">Update {{$title}}</h5><small class="text-secondary">Perbarui informasi {{$row->name}}.</small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
 <div class="modal-body px-4"><div class="row g-3">
  <div class="col-12"><label class="form-label">Nama *</label><input class="form-control" name="name" value="{{$useOld?old('name'):$row->name}}" required></div>
  @if($resource==='suppliers')
   <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="{{$useOld?old('email'):$row->email}}"></div><div class="col-md-6"><label class="form-label">PIC</label><input class="form-control" name="pic" value="{{$useOld?old('pic'):$row->pic}}"></div><div class="col-md-6"><label class="form-label">Telepon</label><input class="form-control" name="phone" value="{{$useOld?old('phone'):$row->phone}}"></div><div class="col-md-6"><label class="form-label">Kota</label><input class="form-control" name="city" value="{{$useOld?old('city'):$row->city}}"></div><div class="col-12"><label class="form-label">Alamat</label><textarea class="form-control" name="address">{{$useOld?old('address'):$row->address}}</textarea></div>
  @elseif($resource==='warehouses')
   <div class="col-md-6"><label class="form-label">PIC</label><input class="form-control" name="pic" value="{{$useOld?old('pic'):$row->pic}}"></div><div class="col-md-6"><label class="form-label">Telepon</label><input class="form-control" name="phone" value="{{$useOld?old('phone'):$row->phone}}"></div><div class="col-12"><label class="form-label">Alamat</label><textarea class="form-control" name="address">{{$useOld?old('address'):$row->address}}</textarea></div>
  @else
   <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="{{$useOld?old('email'):$row->email}}"></div><div class="col-md-6"><label class="form-label">Telepon</label><input class="form-control" name="phone" value="{{$useOld?old('phone'):$row->phone}}"></div><div class="col-md-6"><label class="form-label">Cabang</label><input class="form-control" name="branch" value="{{$useOld?old('branch'):$row->branch}}"></div><div class="col-md-6"><label class="form-label">Area</label><input class="form-control" name="area" value="{{$useOld?old('area'):$row->area}}"></div><div class="col-12"><label class="form-label">Status *</label><select class="form-select" name="status"><option value="active" @selected(($useOld?old('status'):$row->status)==='active')>Aktif</option><option value="inactive" @selected(($useOld?old('status'):$row->status)==='inactive')>Nonaktif</option></select></div>
  @endif
 </div></div><div class="modal-footer border-0 px-4 pb-4"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary px-4">Simpan Perubahan</button></div>
 </form>
</div></div></div>
@endforeach
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){
 document.querySelectorAll('.icon-button[title="Ubah"][href]').forEach(link=>link.addEventListener('click',function(event){event.preventDefault();const id=this.href.split('/').filter(Boolean).slice(-2,-1)[0];bootstrap.Modal.getOrCreateInstance(document.getElementById('editRecord'+id)).show()}));
 @if($errors->any() && old('_editing_id')) bootstrap.Modal.getOrCreateInstance(document.getElementById('editRecord{{old('_editing_id')}}')).show(); @endif
});
</script>
@endpush
@endif
@endsection
