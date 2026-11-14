@extends('layouts.app')
@section('title','Kasir · Penjualan')
@section('content')
<style>
    .pos-shell{--pos-purple:#6d4aff;--pos-ink:#292742;--pos-muted:#858199}
    .pos-heading{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:20px}
    .pos-heading h2{font:800 22px 'Plus Jakarta Sans';margin:0;color:var(--pos-ink)}
    .pos-heading p{margin:5px 0 0;color:var(--pos-muted)}
    .pos-layout{display:grid;grid-template-columns:minmax(0,1.55fr) minmax(320px,.8fr);gap:20px;align-items:start}
    .pos-panel{background:#fff;border:1px solid #efedf5;border-radius:16px;box-shadow:0 5px 22px #30296c0a;overflow:hidden}
    .pos-panel-head{padding:20px 22px;border-bottom:1px solid #efedf5}
    .pos-panel-body{padding:20px 22px}
    .pos-search{position:relative}.pos-search i{position:absolute;left:15px;top:14px;color:#aaa7bb}.pos-search input{padding-left:42px}
    .product-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:18px}
    .product-tile{height:100%;width:100%;min-height:140px;text-align:left;padding:15px;border:1px solid #eceaf4;border-radius:13px;background:#fff;color:var(--pos-ink);transition:.18s;display:flex;flex-direction:column;justify-content:space-between}
    .product-tile:hover{border-color:#b9aaff;background:#fbfaff;transform:translateY(-1px)}
    .product-tile.active{border:2px solid var(--pos-purple);background:#f6f3ff;padding:14px;box-shadow:0 4px 14px #6d4aff16}
    .product-tile:disabled{opacity:.5;cursor:not-allowed;background:#f7f6fa}
    .product-name{font-weight:700;line-height:1.35}.product-meta{font-size:11px;color:var(--pos-muted);margin-top:5px}
    .product-price{font-size:14px;font-weight:800;color:var(--pos-purple);margin-top:12px}
    .stock-pill{font-size:10px;border-radius:20px;background:#e8f8f1;color:#14865f;padding:4px 8px;white-space:nowrap}
    .stock-pill.empty{background:#fff0ee;color:#c74737}
    .cart-panel{position:sticky;top:92px}
    .cart-empty{text-align:center;color:var(--pos-muted);padding:35px 10px}
    .cart-empty i{font-size:28px;color:#c7c2d8;margin-bottom:10px}
    .cart-item{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:15px 0;border-bottom:1px solid #efedf5}
    .qty-control{display:inline-flex;align-items:center;gap:11px;background:#f5f3fb;border-radius:9px;padding:4px}
    .qty-control button{width:28px;height:28px;border:0;border-radius:7px;background:#fff;color:#5540b8;font-size:16px;font-weight:700}
    .qty-control span{min-width:20px;text-align:center;font-weight:700}
    .cart-total{display:flex;justify-content:space-between;align-items:center;padding-top:16px;margin-top:14px;border-top:1px dashed #dedbea}
    .cart-total strong{font-size:21px;color:var(--pos-ink)}
    .btn-checkout{height:48px;font-size:14px;font-weight:700}
    @media(max-width:1000px){.pos-layout{grid-template-columns:1fr}.cart-panel{position:static}.product-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:540px){.pos-heading{align-items:flex-start;flex-direction:column}.product-grid{grid-template-columns:1fr 1fr;gap:9px}.product-tile{min-height:130px;padding:12px}.product-tile.active{padding:11px}.pos-panel-head,.pos-panel-body{padding:16px}}
</style>

<div class="pos-shell">
    <div class="pos-heading">
        <div><h2>Penjualan Baru</h2><p>Pilih produk, atur jumlah, lalu simpan transaksi.</p></div>
        <a class="btn btn-outline-primary" href="{{route('sales.history')}}"><i class="fa-solid fa-clock-rotate-left me-2"></i>Riwayat Penjualan</a>
    </div>

    @if($errors->any())<div class="alert alert-danger border-0"><i class="fa-solid fa-circle-exclamation me-2"></i>{{$errors->first()}}</div>@endif

    <form method="post" action="{{route('stock.outbound.store')}}" data-confirm-action data-confirm-title="Simpan penjualan" data-confirm-message="Pastikan barang dan jumlah sudah benar. Simpan penjualan ini?" id="cashierSaleForm">
        @csrf
        <input type="hidden" name="transaction_date" value="{{old('transaction_date',today()->format('Y-m-d'))}}">
        <input type="hidden" name="product_id" id="selectedProductId" value="{{old('product_id')}}">
        <input type="hidden" name="price" id="salePrice" value="{{old('price')}}">

        <div class="pos-layout">
            <section class="pos-panel">
                <div class="pos-panel-head">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div><h5 class="fw-bold mb-1">Pilih Produk</h5><small class="text-secondary">{{$products->count()}} produk tersedia</small></div>
                        <span class="badge rounded-pill text-bg-light"><i class="fa-regular fa-calendar me-1"></i>{{today()->format('d/m/Y')}}</span>
                    </div>
                    <div class="pos-search"><i class="fa-solid fa-magnifying-glass"></i><input id="productSearch" class="form-control" type="search" placeholder="Cari nama atau kategori produk..." autocomplete="off"></div>
                </div>
                <div class="pos-panel-body">
                    @if($products->isEmpty())
                        <div class="cart-empty"><i class="fa-solid fa-box-open d-block"></i>Belum ada produk aktif untuk dijual.</div>
                    @else
                        <div class="product-grid" id="productGrid">
                            @foreach($products as $product)
                                <button type="button" class="product-tile" data-product-tile data-id="{{$product->id}}" data-name="{{$product->name}}" data-search="{{strtolower($product->name.' '.$product->brand.' '.$product->category.' '.$product->sku)}}" data-price="{{$product->selling_price}}" data-stock="{{$product->current_stock}}" data-unit="{{$product->unit}}" @disabled($product->current_stock < 1)>
                                    <span>
                                        <span class="d-flex justify-content-between align-items-start gap-2"><span class="product-name">{{$product->name}}</span><span class="stock-pill {{$product->current_stock < 1?'empty':''}}">Stok {{$product->current_stock}}</span></span>
                                        <span class="product-meta">{{$product->brand ?: $product->category ?: $product->sku}}</span>
                                    </span>
                                    <span class="product-price">Rp {{number_format($product->selling_price,0,',','.')}} <small class="fw-normal text-secondary">/ {{$product->unit}}</small></span>
                                </button>
                            @endforeach
                        </div>
                        <div id="emptySearch" class="cart-empty d-none"><i class="fa-solid fa-magnifying-glass d-block"></i>Produk tidak ditemukan.</div>
                    @endif
                </div>
            </section>

            <aside class="pos-panel cart-panel">
                <div class="pos-panel-head d-flex justify-content-between align-items-center"><div><h5 class="fw-bold mb-1">Keranjang</h5><small class="text-secondary">Ringkasan penjualan</small></div><span class="badge rounded-pill text-bg-primary" id="cartCount">0 item</span></div>
                <div class="pos-panel-body">
                    <div class="cart-empty" id="cartEmpty"><i class="fa-solid fa-basket-shopping d-block"></i>Pilih produk untuk memulai penjualan.</div>
                    <div class="d-none" id="cartItem">
                        <div class="cart-item"><div class="min-w-0"><div class="fw-bold text-truncate" id="cartProductName"></div><small class="text-secondary" id="cartUnitPrice"></small></div><button type="button" class="btn btn-sm text-danger" id="removeProduct" title="Hapus produk"><i class="fa-regular fa-trash-can"></i></button></div>
                        <div class="d-flex justify-content-between align-items-center py-3"><span class="small fw-semibold">Jumlah</span><div class="qty-control"><button type="button" id="qtyMinus" aria-label="Kurangi jumlah">−</button><span id="qtyDisplay">1</span><button type="button" id="qtyPlus" aria-label="Tambah jumlah">+</button></div></div>
                        <input type="hidden" name="qty" id="saleQty" value="{{old('qty',1)}}">
                        <label for="customerName" class="form-label mt-2 mb-1">Nama pelanggan <span class="text-secondary fw-normal">(opsional)</span></label>
                        <input class="form-control" id="customerName" name="destination" value="{{old('destination')}}" maxlength="150" placeholder="Pelanggan umum">
                        <div class="cart-total"><span class="fw-semibold">Total</span><strong id="saleTotal">Rp 0</strong></div>
                        <small class="text-secondary d-block mt-2">Harga mengikuti harga jual produk.</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-checkout mt-4" id="checkoutButton" disabled><i class="fa-solid fa-cash-register me-2"></i>Simpan Penjualan</button>
                    <a href="{{route('sales.history')}}" class="btn btn-light w-100 mt-2">Lihat Riwayat</a>
                </div>
            </aside>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tiles = [...document.querySelectorAll('[data-product-tile]')];
    const search = document.getElementById('productSearch');
    const selectedId = document.getElementById('selectedProductId');
    const priceInput = document.getElementById('salePrice');
    const qtyInput = document.getElementById('saleQty');
    const cartEmpty = document.getElementById('cartEmpty');
    const cartItem = document.getElementById('cartItem');
    const checkout = document.getElementById('checkoutButton');
    let selectedTile = null;
    let price = 0;

    const money = amount => 'Rp ' + Math.round(amount).toLocaleString('id-ID');
    function updateTotal() {
        const qty = Math.max(1, parseInt(qtyInput.value || '1', 10));
        qtyInput.value = qty;
        document.getElementById('qtyDisplay').textContent = qty;
        document.getElementById('saleTotal').textContent = money(price * qty);
        document.getElementById('cartCount').textContent = `${qty} item`;
    }
    function selectProduct(tile) {
        if (selectedTile) selectedTile.classList.remove('active');
        selectedTile = tile;
        tile.classList.add('active');
        selectedId.value = tile.dataset.id;
        price = Number(tile.dataset.price || 0);
        priceInput.value = price;
        document.getElementById('cartProductName').textContent = tile.dataset.name;
        document.getElementById('cartUnitPrice').textContent = `${money(price)} / ${tile.dataset.unit}`;
        const qty = Math.min(Math.max(1, parseInt(qtyInput.value || '1', 10)), Number(tile.dataset.stock));
        qtyInput.value = qty;
        cartEmpty.classList.add('d-none');
        cartItem.classList.remove('d-none');
        checkout.disabled = false;
        updateTotal();
    }
    tiles.forEach(tile => tile.addEventListener('click', () => selectProduct(tile)));
    if (search) search.addEventListener('input', () => {
        const term = search.value.trim().toLowerCase();
        let visible = 0;
        tiles.forEach(tile => {
            const matches = tile.dataset.search.includes(term);
            tile.classList.toggle('d-none', !matches);
            if (matches) visible++;
        });
        document.getElementById('emptySearch')?.classList.toggle('d-none', visible > 0);
        document.getElementById('productGrid')?.classList.toggle('d-none', visible === 0);
    });
    document.getElementById('qtyMinus').addEventListener('click', () => { qtyInput.value = Math.max(1, Number(qtyInput.value) - 1); updateTotal(); });
    document.getElementById('qtyPlus').addEventListener('click', () => {
        if (selectedTile && Number(qtyInput.value) < Number(selectedTile.dataset.stock)) qtyInput.value = Number(qtyInput.value) + 1;
        updateTotal();
    });
    document.getElementById('removeProduct').addEventListener('click', () => {
        selectedTile?.classList.remove('active');
        selectedTile = null;
        selectedId.value = '';
        priceInput.value = '';
        price = 0;
        qtyInput.value = 1;
        cartItem.classList.add('d-none');
        cartEmpty.classList.remove('d-none');
        checkout.disabled = true;
        document.getElementById('cartCount').textContent = '0 item';
    });
    const previousSelection = tiles.find(tile => tile.dataset.id === selectedId.value);
    if (previousSelection) selectProduct(previousSelection);
});
</script>
@endpush
