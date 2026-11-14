@extends('layouts.app')
@section('title','Kasir - Penjualan')
@section('content')
<style>
    .pos-shell{--pos-purple:#6d4aff;--pos-ink:#292742;--pos-muted:#858199;min-width:0}
    .pos-heading{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:20px}
    .pos-heading h2{font:800 22px 'Plus Jakarta Sans';margin:0;color:var(--pos-ink)}.pos-heading p{margin:5px 0 0;color:var(--pos-muted)}
    .pos-layout{display:grid;grid-template-columns:minmax(0,1.55fr) minmax(320px,.8fr);gap:20px;align-items:start}
    .pos-panel{min-width:0;background:#fff;border:1px solid #efedf5;border-radius:16px;box-shadow:0 5px 22px #30296c0a;overflow:hidden}
    .pos-panel-head{padding:20px 22px;border-bottom:1px solid #efedf5}.pos-panel-body{padding:20px 22px}
    .pos-search{position:relative}.pos-search i{position:absolute;left:15px;top:14px;color:#aaa7bb}.pos-search input{padding-left:42px}
    .product-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:18px}
    .product-tile{width:100%;min-width:0;min-height:145px;text-align:left;padding:14px;border:1px solid #eceaf4;border-radius:13px;background:#fff;color:var(--pos-ink);transition:.18s;display:flex;flex-direction:column;justify-content:space-between}
    .product-tile:hover{border-color:#b9aaff;background:#fbfaff}.product-tile.in-cart{border-color:#9c88ff;background:#f8f6ff}
    .product-tile:disabled{opacity:.5;cursor:not-allowed;background:#f7f6fa}
    .product-name{font-weight:700;line-height:1.35;overflow-wrap:anywhere}.product-meta{font-size:11px;color:var(--pos-muted);margin-top:5px}
    .product-price{font-size:14px;font-weight:800;color:var(--pos-purple);margin-top:12px}.stock-pill{font-size:10px;border-radius:20px;background:#e8f8f1;color:#14865f;padding:4px 8px;white-space:nowrap}.stock-pill.empty{background:#fff0ee;color:#c74737}
    .product-add-label{font-size:11px;color:#6249d1;font-weight:700;margin-top:10px}.cart-panel{position:sticky;top:92px}
    .cart-empty{text-align:center;color:var(--pos-muted);padding:30px 10px}.cart-empty i{font-size:27px;color:#c7c2d8;margin-bottom:10px}
    .cart-lines{max-height:43vh;overflow-y:auto;overscroll-behavior:contain}.cart-line{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;padding:13px 0;border-bottom:1px solid #efedf5}
    .cart-line-name{font-size:13px;font-weight:700;overflow-wrap:anywhere}.cart-line-meta{font-size:11px;color:var(--pos-muted);margin-top:3px}.cart-line-subtotal{font-size:12px;font-weight:700;color:#5138bd;text-align:right;white-space:nowrap}
    .cart-line-actions{display:flex;align-items:center;gap:6px;margin-top:8px}.qty-control{display:inline-flex;align-items:center;gap:8px;background:#f5f3fb;border-radius:9px;padding:3px}
    .qty-control button{width:28px;height:28px;border:0;border-radius:7px;background:#fff;color:#5540b8;font-size:16px;font-weight:700}.qty-control span{min-width:22px;text-align:center;font-weight:700;font-size:12px}
    .remove-line{width:30px;height:30px;border:0;border-radius:8px;background:#fff0f0;color:#c43f4a}
    .cart-total{display:flex;justify-content:space-between;align-items:center;padding-top:15px;margin-top:12px;border-top:1px dashed #dedbea}.cart-total strong{font-size:20px;color:var(--pos-ink)}
    .btn-checkout{height:46px;font-size:14px;font-weight:700}.mobile-sale-bar{display:none}
    @media(max-width:1100px){.pos-layout{grid-template-columns:minmax(0,1.2fr) minmax(290px,.9fr);gap:14px}.product-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:900px){.pos-layout{grid-template-columns:1fr}.cart-panel{position:static}.product-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.cart-lines{max-height:none}}
    @media(max-width:900px){.mobile-sale-bar{position:fixed;display:flex;align-items:center;justify-content:space-between;gap:12px;left:10px;right:10px;bottom:10px;z-index:1010;padding:11px 12px;border:1px solid #e8e4f2;border-radius:14px;background:#fff;box-shadow:0 8px 30px #17143c22}.mobile-sale-summary{min-width:0}.mobile-sale-summary small{display:block;color:var(--pos-muted);font-size:10px}.mobile-sale-summary strong{display:block;color:var(--pos-ink);font-size:15px;white-space:nowrap}.mobile-sale-bar .btn{white-space:nowrap}.pos-shell{padding-bottom:72px}}
    @media(max-width:600px){.pos-heading{align-items:flex-start;flex-direction:column}.pos-heading>.btn{width:100%}.product-grid{gap:8px}.product-tile{min-height:135px;padding:11px}.pos-panel-head,.pos-panel-body{padding:15px}.cart-line{grid-template-columns:minmax(0,1fr) auto}.mobile-sale-bar{position:fixed;display:flex;align-items:center;justify-content:space-between;gap:12px;left:10px;right:10px;bottom:10px;z-index:1010;padding:11px 12px;border:1px solid #e8e4f2;border-radius:14px;background:#fff;box-shadow:0 8px 30px #17143c22}.mobile-sale-summary{min-width:0}.mobile-sale-summary small{display:block;color:var(--pos-muted);font-size:10px}.mobile-sale-summary strong{display:block;color:var(--pos-ink);font-size:15px;white-space:nowrap}.mobile-sale-bar .btn{white-space:nowrap}.pos-shell{padding-bottom:72px}}
</style>

<div class="pos-shell">
    <div class="pos-heading">
        <div><h2>Penjualan Baru</h2><p>Pilih beberapa produk, atur jumlah di keranjang, lalu simpan dalam satu nota.</p></div>
        <a class="btn btn-outline-primary" href="{{route('sales.history')}}"><i class="fa-solid fa-clock-rotate-left me-2"></i>Riwayat Penjualan</a>
    </div>

    @if($errors->any())<div class="alert alert-danger border-0"><i class="fa-solid fa-circle-exclamation me-2"></i>{{$errors->first()}}</div>@endif

    <form method="post" action="{{route('stock.outbound.store')}}" data-confirm-action data-confirm-title="Simpan penjualan" data-confirm-message="Pastikan semua barang dan jumlah di keranjang sudah benar." id="cashierSaleForm">
        @csrf
        <input type="hidden" name="transaction_date" value="{{old('transaction_date',today()->format('Y-m-d'))}}">
        <div id="cartHiddenFields"></div>

        <div class="pos-layout">
            <section class="pos-panel">
                <div class="pos-panel-head">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><div><h5 class="fw-bold mb-1">Pilih Produk</h5><small class="text-secondary"><span id="availableProductCount">{{$products->count()}}</span> produk tersedia</small></div><span class="badge rounded-pill text-bg-light"><i class="fa-regular fa-calendar me-1"></i>{{today()->format('d/m/Y')}}</span></div>
                    <div class="pos-search"><i class="fa-solid fa-magnifying-glass"></i><input id="productSearch" class="form-control" type="search" placeholder="Cari nama atau kategori produk..." autocomplete="off"></div>
                </div>
                <div class="pos-panel-body">
                    @if($products->isEmpty())
                        <div class="cart-empty"><i class="fa-solid fa-box-open d-block"></i>Belum ada produk aktif untuk dijual.</div>
                    @else
                        <div class="product-grid" id="productGrid">
                            @foreach($products as $product)
                                <button type="button" class="product-tile" data-product-tile data-id="{{$product->id}}" data-name="{{$product->name}}" data-search="{{strtolower($product->name.' '.$product->brand.' '.$product->category.' '.$product->sku)}}" data-price="{{$product->selling_price}}" data-stock="{{$product->current_stock}}" data-unit="{{$product->unit}}" @disabled($product->current_stock < 1)>
                                    <span><span class="d-flex justify-content-between align-items-start gap-2"><span class="product-name">{{$product->name}}</span><span class="stock-pill {{$product->current_stock < 1?'empty':''}}">Stok {{$product->current_stock}}</span></span><span class="product-meta">{{$product->brand ?: $product->category ?: $product->sku}}</span></span>
                                    <span><span class="product-price d-block">Rp {{number_format($product->selling_price,0,',','.')}} <small class="fw-normal text-secondary">/ {{$product->unit}}</small></span><span class="product-add-label" data-add-label><i class="fa-solid fa-plus me-1"></i>Tambah ke keranjang</span></span>
                                </button>
                            @endforeach
                        </div>
                        <div id="emptySearch" class="cart-empty d-none"><i class="fa-solid fa-magnifying-glass d-block"></i>Produk tidak ditemukan.</div>
                    @endif
                </div>
            </section>

            <aside class="pos-panel cart-panel">
                <div class="pos-panel-head d-flex justify-content-between align-items-center"><div><h5 class="fw-bold mb-1">Keranjang</h5><small class="text-secondary">Semua produk masuk dalam satu transaksi</small></div><span class="badge rounded-pill text-bg-primary" id="cartCount">0 unit</span></div>
                <div class="pos-panel-body">
                    <div class="cart-empty" id="cartEmpty"><i class="fa-solid fa-basket-shopping d-block"></i>Belum ada produk. Pilih barang untuk menambahkan.</div>
                    <div class="cart-lines" id="cartLines"></div>
                    <div class="cart-total"><span class="fw-semibold">Total Penjualan</span><strong id="saleTotal">Rp 0</strong></div>
                    <label for="customerName" class="form-label mt-3 mb-1">Nama pelanggan <span class="text-secondary fw-normal">(opsional)</span></label>
                    <input class="form-control" id="customerName" name="destination" value="{{old('destination')}}" maxlength="150" placeholder="Pelanggan umum">
                    <label for="saleNotes" class="form-label mt-3 mb-1">Catatan <span class="text-secondary fw-normal">(opsional)</span></label>
                    <textarea class="form-control" id="saleNotes" name="notes" rows="2" maxlength="2000" placeholder="Catatan transaksi">{{old('notes')}}</textarea>
                    <small class="text-secondary d-block mt-2">Harga mengikuti harga jual produk. Stok diperiksa kembali saat transaksi disimpan.</small>
                    <button type="submit" class="btn btn-primary w-100 btn-checkout mt-3" id="checkoutButton" disabled><i class="fa-solid fa-cash-register me-2"></i>Simpan Penjualan</button>
                    <a href="{{route('sales.history')}}" class="btn btn-light w-100 mt-2">Lihat Riwayat</a>
                </div>
            </aside>
        </div>
        <div class="mobile-sale-bar"><div class="mobile-sale-summary"><small id="mobileCartCount">Keranjang kosong</small><strong id="mobileSaleTotal">Rp 0</strong></div><button type="submit" class="btn btn-primary" id="mobileCheckoutButton" disabled>Simpan Penjualan</button></div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tiles = [...document.querySelectorAll('[data-product-tile]')];
    const cart = new Map();
    const hiddenFields = document.getElementById('cartHiddenFields');
    const cartLines = document.getElementById('cartLines');
    const cartEmpty = document.getElementById('cartEmpty');
    const checkout = document.getElementById('checkoutButton');
    const mobileCheckout = document.getElementById('mobileCheckoutButton');
    const money = amount => 'Rp ' + Math.round(amount).toLocaleString('id-ID');

    function renderCart() {
        cartLines.replaceChildren();
        hiddenFields.replaceChildren();
        let total = 0;
        let totalUnits = 0;

        [...cart.values()].forEach((item, index) => {
            const subtotal = item.price * item.qty;
            total += subtotal;
            totalUnits += item.qty;

            const line = document.createElement('div');
            line.className = 'cart-line';
            line.innerHTML = `<div><div class="cart-line-name"></div><div class="cart-line-meta"></div><div class="cart-line-actions"><div class="qty-control"><button type="button" data-decrease aria-label="Kurangi jumlah">−</button><span>${item.qty}</span><button type="button" data-increase aria-label="Tambah jumlah">+</button></div><button type="button" class="remove-line" data-remove aria-label="Hapus produk dari keranjang"><i class="fa-regular fa-trash-can"></i></button></div></div><div class="cart-line-subtotal">${money(subtotal)}</div>`;
            line.querySelector('.cart-line-name').textContent = item.name;
            line.querySelector('.cart-line-meta').textContent = `${money(item.price)} / ${item.unit}`;
            line.querySelector('[data-decrease]').addEventListener('click', () => {
                item.qty--;
                if (item.qty <= 0) cart.delete(item.id);
                renderCart();
            });
            line.querySelector('[data-increase]').addEventListener('click', () => {
                if (item.qty < item.stock) item.qty++;
                renderCart();
            });
            line.querySelector('[data-remove]').addEventListener('click', () => { cart.delete(item.id); renderCart(); });
            cartLines.appendChild(line);

            const productId = document.createElement('input');
            productId.type = 'hidden'; productId.name = `items[${index}][product_id]`; productId.value = item.id;
            const qty = document.createElement('input');
            qty.type = 'hidden'; qty.name = `items[${index}][qty]`; qty.value = item.qty;
            hiddenFields.append(productId, qty);
        });

        tiles.forEach(tile => {
            const item = cart.get(tile.dataset.id);
            tile.classList.toggle('in-cart', Boolean(item));
            tile.querySelector('[data-add-label]').innerHTML = item
                ? `<i class="fa-solid fa-check me-1"></i>Di keranjang: ${item.qty} · tambah 1`
                : '<i class="fa-solid fa-plus me-1"></i>Tambah ke keranjang';
        });

        const hasItems = cart.size > 0;
        cartEmpty.classList.toggle('d-none', hasItems);
        checkout.disabled = !hasItems;
        mobileCheckout.disabled = !hasItems;
        document.getElementById('cartCount').textContent = `${cart.size} produk · ${totalUnits} unit`;
        document.getElementById('saleTotal').textContent = money(total);
        document.getElementById('mobileCartCount').textContent = hasItems ? `${cart.size} produk · ${totalUnits} unit` : 'Keranjang kosong';
        document.getElementById('mobileSaleTotal').textContent = money(total);
    }

    tiles.forEach(tile => tile.addEventListener('click', () => {
        const id = tile.dataset.id;
        const item = cart.get(id) || {id, name:tile.dataset.name, price:Number(tile.dataset.price || 0), stock:Number(tile.dataset.stock || 0), unit:tile.dataset.unit, qty:0};
        if (item.qty < item.stock) item.qty++;
        cart.set(id, item);
        renderCart();
    }));

    const search = document.getElementById('productSearch');
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

    const oldItems = @json(old('items', []));
    Object.values(oldItems).forEach(line => {
        const tile = tiles.find(candidate => candidate.dataset.id === String(line.product_id));
        if (!tile) return;
        const qty = Math.min(Number(line.qty || 0), Number(tile.dataset.stock || 0));
        if (qty < 1) return;
        cart.set(tile.dataset.id, {id:tile.dataset.id, name:tile.dataset.name, price:Number(tile.dataset.price || 0), stock:Number(tile.dataset.stock || 0), unit:tile.dataset.unit, qty});
    });
    renderCart();
});
</script>
@endpush
