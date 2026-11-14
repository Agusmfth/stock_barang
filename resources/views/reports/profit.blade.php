@extends('layouts.app')
@section('title','Laporan Keuntungan')
@section('content')
<style>
    .profit-hero{background:linear-gradient(115deg,#302078,#6548d8 62%,#8d67e8);border-radius:16px;color:#fff;padding:23px 26px}
    .profit-hero h2{font:800 21px 'Plus Jakarta Sans';margin:0 0 5px}.profit-hero p{margin:0;opacity:.78}
    .profit-card{border:1px solid #efedf5;border-radius:14px;background:#fff;padding:19px;height:100%;box-shadow:0 5px 22px #30296c08}
    .profit-card small{color:#858199;font-weight:600}.profit-card strong{display:block;font:800 22px 'Plus Jakarta Sans';margin-top:10px;color:#292742}
    .profit-card.profit-main{background:#f2efff;border-color:#e3dcff}.profit-main strong{color:#5234c8}
    .period-label{font:700 15px 'Plus Jakarta Sans';color:#292742}
    .period-range{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:8px;padding:12px 15px;border-radius:10px;background:#f7f6fb;border:1px solid #efedf5}
    .period-range span{font-size:12px;color:#858199}.period-range strong{font:700 13px 'Plus Jakarta Sans';color:#4e37ae}
    .profit-filter-grid{display:grid;grid-template-columns:minmax(180px,1fr) minmax(220px,1.2fr) minmax(170px,1fr) 228px;gap:16px;align-items:start}
    .profit-filter-field{min-width:0;display:grid;grid-template-rows:18px 44px 17px;row-gap:8px}.profit-filter-field .form-label{display:block;height:18px;line-height:18px;margin:0}.profit-filter-field .form-control,.profit-filter-field .form-select{height:44px;min-height:44px}
    .profit-filter-help{display:block;min-height:17px;margin:0;font-size:11px;line-height:17px;color:#858199;white-space:nowrap}
    .profit-filter-button-wrap{display:grid;grid-template-rows:18px 44px 17px;row-gap:8px}.profit-filter-submit{grid-row:2;height:44px;min-height:44px;width:100%;margin:0;font-weight:700}
    .profit-table{width:100%;min-width:780px;table-layout:fixed;border-collapse:collapse}.profit-table th,.profit-table td{vertical-align:middle}.profit-table th{padding:12px 14px!important}.profit-table td{padding:13px 14px!important;overflow-wrap:anywhere}.profit-table .num{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}.profit-table tbody tr:nth-child(even){background:#fcfbfe}.profit-table tfoot td{background:#f7f5fc;font-weight:700;border-top:1px solid #e5e1ef}.profit-details{min-width:1250px}.profit-details td:nth-child(2),.profit-details td:nth-child(3),.profit-details td:nth-child(6){white-space:nowrap}.profit-return{background:#fff8f4!important}
    @media(max-width:900px){.profit-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:560px){.profit-filter-grid{grid-template-columns:1fr}.profit-filter-help{white-space:normal}}
    @media print{.no-print{display:none!important}.content{padding:0!important}.card,.profit-card{box-shadow:none}}
</style>

<section class="profit-hero mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div><h2>Keuntungan Penjualan</h2><p>Ringkasan omzet, modal barang terjual, dan laba kotor.</p></div>
    <div class="d-flex flex-wrap gap-2 no-print">
        <a class="btn btn-light" href="{{route('reports.profit.export',['format'=>'pdf','period'=>$period,'date'=>request('date',today()->toDateString()),'date_from'=>$selectedFrom,'date_to'=>$selectedTo,'month'=>$selectedMonth])}}"><i class="fa-regular fa-file-pdf me-2 text-danger"></i>Unduh PDF</a>
        <a class="btn btn-outline-light" href="{{route('reports.profit.export',['format'=>'excel','period'=>$period,'date'=>request('date',today()->toDateString()),'date_from'=>$selectedFrom,'date_to'=>$selectedTo,'month'=>$selectedMonth])}}"><i class="fa-regular fa-file-excel me-2"></i>Unduh Excel</a>
    </div>
</section>

<div class="card mb-4 no-print"><div class="card-body p-4"><form method="get" action="{{route('reports.profit')}}">
    <div class="profit-filter-grid">
        <div class="profit-filter-field"><label class="form-label" for="period">Periode</label><select class="form-select" id="period" name="period"><option value="daily" @selected($period==='daily')>Harian</option><option value="weekly" @selected($period==='weekly')>Mingguan</option><option value="monthly" @selected($period==='monthly')>Bulanan</option></select><small class="profit-filter-help">Pilih jenis rekap laporan.</small></div>
        <div class="profit-filter-field" id="dateField" style="{{$period==='daily'?'':'display:none'}}"><label class="form-label" for="date">Tanggal laporan</label><input class="form-control" id="date" name="date" type="date" value="{{request('date',today()->toDateString())}}"><small class="profit-filter-help">Pilih satu tanggal.</small></div>
        <div class="profit-filter-field" id="fromField" style="{{$period==='weekly'?'':'display:none'}}"><label class="form-label" for="dateFrom">Dari tanggal</label><input class="form-control" id="dateFrom" name="date_from" type="date" value="{{$selectedFrom}}"><small class="profit-filter-help">Tanggal awal rentang.</small></div>
        <div class="profit-filter-field" id="toField" style="{{$period==='weekly'?'':'display:none'}}"><label class="form-label" for="dateTo">Sampai tanggal</label><input class="form-control" id="dateTo" name="date_to" type="date" value="{{$selectedTo}}"><small class="profit-filter-help" id="rangeHelp">Rentang maksimal 7 hari.</small></div>
        <div class="profit-filter-field" id="monthField" style="{{$period==='monthly'?'':'display:none'}}"><label class="form-label" for="month">Bulan laporan</label><input class="form-control" id="month" name="month" type="month" value="{{$selectedMonth}}"><small class="profit-filter-help">Pilih bulan yang ingin dilihat.</small></div>
        <div class="profit-filter-button-wrap"><button class="btn btn-primary profit-filter-submit"><i class="fa-solid fa-filter me-2"></i>Tampilkan</button></div>
    </div>
    <div class="period-range mt-3"><span><i class="fa-regular fa-calendar me-2"></i>Rentang laporan</span><strong id="periodRange">{{$periodLabel}}</strong></div>
</form></div></div>

@if($legacyCostItems > 0)<div class="alert alert-warning border-0"><i class="fa-solid fa-circle-info me-2"></i>{{$legacyCostItems}} item transaksi lama belum memiliki snapshot harga modal. Perhitungannya memakai harga modal produk saat ini.</div>@endif

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="profit-card"><small>Omzet</small><strong>Rp {{number_format($totalRevenue,0,',','.')}}</strong></div></div>
    <div class="col-sm-6 col-xl-3"><div class="profit-card"><small>Modal Barang Terjual</small><strong>Rp {{number_format($totalCost,0,',','.')}}</strong></div></div>
    <div class="col-sm-6 col-xl-3"><div class="profit-card profit-main"><small>Laba Kotor</small><strong>Rp {{number_format($totalProfit,0,',','.')}}</strong></div></div>
    <div class="col-sm-6 col-xl-3"><div class="profit-card"><small>Jumlah Transaksi</small><strong>{{$totalTransactions}}</strong></div></div>
</div>

<div class="card mb-4"><div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-4 border-bottom"><div><div class="section-card-title">Ringkasan {{$period==='daily'?'Harian':($period==='weekly'?'Mingguan':'Bulanan')}}</div><small class="text-secondary">Laba kotor dihitung dari omzet penjualan dikurangi modal barang.</small></div><span class="badge badge-soft">{{$periodLabel}}</span></div>
    <div class="table-responsive"><table class="table align-middle profit-table"><colgroup><col style="width:22%"><col style="width:14%"><col style="width:21%"><col style="width:21%"><col style="width:22%"></colgroup><thead><tr><th>Tanggal</th><th class="text-end">Transaksi</th><th class="text-end">Omzet</th><th class="text-end">Modal</th><th class="text-end">Laba Kotor</th></tr></thead><tbody>
        @forelse($rows as $row)
            <tr><td class="fw-semibold">{{$row['date']->translatedFormat('d M Y')}}</td><td class="num">{{$row['transactions']}}</td><td class="num">Rp {{number_format($row['revenue'],0,',','.')}}</td><td class="num">Rp {{number_format($row['cost'],0,',','.')}}</td><td class="num fw-bold {{$row['profit']<0?'text-danger':'text-success'}}">Rp {{number_format($row['profit'],0,',','.')}}</td></tr>
        @empty
            <tr><td colspan="5" class="text-center py-5 text-secondary">Belum ada data penjualan pada periode ini.</td></tr>
        @endforelse
    </tbody><tfoot><tr><td>Total</td><td class="num">{{$totalTransactions}}</td><td class="num">Rp {{number_format($totalRevenue,0,',','.')}}</td><td class="num">Rp {{number_format($totalCost,0,',','.')}}</td><td class="num {{$totalProfit<0?'text-danger':'text-success'}}">Rp {{number_format($totalProfit,0,',','.')}}</td></tr></tfoot></table></div>
</div>

<div class="card"><div class="p-4 border-bottom"><div class="section-card-title">Rincian Penjualan</div><small class="text-secondary">Detail transaksi yang masuk ke perhitungan laporan.</small></div>
    <div class="table-responsive"><table class="table align-middle profit-table profit-details"><colgroup><col style="width:10%"><col style="width:9%"><col style="width:14%"><col style="width:12%"><col style="width:17%"><col style="width:6%"><col style="width:12%"><col style="width:10%"><col style="width:10%"></colgroup><thead><tr><th>Waktu</th><th>Jenis</th><th>No. Transaksi</th><th>Kasir</th><th>Produk</th><th class="text-end">Qty</th><th class="text-end">Omzet</th><th class="text-end">Modal</th><th class="text-end">Laba</th></tr></thead><tbody>
        @forelse($details as $detail)
            <tr class="{{$detail['kind']==='Retur'?'profit-return':''}}"><td>{{$detail['date']->format('d/m/Y')}}<small class="d-block text-secondary">{{$detail['date']->format('H:i')}}</small></td><td><span class="badge {{$detail['kind']==='Retur'?'text-bg-warning':'badge-soft'}}">{{$detail['kind']}}</span></td><td>{{$detail['reference']}}</td><td>{{$detail['cashier']}}</td><td>{{$detail['product']}}</td><td class="num">{{$detail['qty']}}</td><td class="num">Rp {{number_format($detail['revenue'],0,',','.')}}</td><td class="num">Rp {{number_format($detail['cost'],0,',','.')}}</td><td class="num fw-semibold {{$detail['profit']<0?'text-danger':'text-success'}}">Rp {{number_format($detail['profit'],0,',','.')}}</td></tr>
        @empty
            <tr><td colspan="9" class="text-center py-5 text-secondary">Belum ada rincian penjualan atau retur.</td></tr>
        @endforelse
    </tbody></table></div>
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const period = document.getElementById('period');
    const dateInput = document.getElementById('date');
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');
    const monthInput = document.getElementById('month');
    const rangeHelp = document.getElementById('rangeHelp');
    const range = document.getElementById('periodRange');
    const submitButton = document.querySelector('.profit-filter-submit');
    const formatter = new Intl.DateTimeFormat('id-ID', {day:'numeric', month:'short', year:'numeric'});

    function updatePeriodFields() {
        const selectedPeriod = period.value;
        submitButton.disabled = false;
        document.getElementById('dateField').style.display = selectedPeriod === 'daily' ? '' : 'none';
        document.getElementById('fromField').style.display = selectedPeriod === 'weekly' ? '' : 'none';
        document.getElementById('toField').style.display = selectedPeriod === 'weekly' ? '' : 'none';
        document.getElementById('monthField').style.display = selectedPeriod === 'monthly' ? '' : 'none';

        if (selectedPeriod === 'daily') {
            if (!dateInput.value) return;
            const [year, month, day] = dateInput.value.split('-').map(Number);
            const selectedDate = new Date(year, month - 1, day);
            range.textContent = formatter.format(selectedDate);
            return;
        }

        if (selectedPeriod === 'weekly') {
            if (!dateFrom.value || !dateTo.value) return;
            const [fromYear, fromMonth, fromDay] = dateFrom.value.split('-').map(Number);
            const [toYear, toMonth, toDay] = dateTo.value.split('-').map(Number);
            const start = new Date(fromYear, fromMonth - 1, fromDay);
            const end = new Date(toYear, toMonth - 1, toDay);
            const days = Math.round((end - start) / 86400000);
            dateTo.min = dateFrom.value;
            const maxEnd = new Date(start);
            maxEnd.setDate(start.getDate() + 6);
            const maxMonth = String(maxEnd.getMonth() + 1).padStart(2, '0');
            const maxDay = String(maxEnd.getDate()).padStart(2, '0');
            dateTo.max = `${maxEnd.getFullYear()}-${maxMonth}-${maxDay}`;
            const valid = days >= 0 && days <= 6;
            submitButton.disabled = !valid;
            rangeHelp.classList.toggle('text-danger', !valid);
            rangeHelp.textContent = valid ? 'Rentang maksimal 7 hari.' : 'Rentang tanggal maksimal 7 hari.';
            range.textContent = valid ? `${formatter.format(start)} – ${formatter.format(end)}` : 'Periksa kembali rentang tanggal.';
            return;
        }

        if (!monthInput.value) return;
        const [year, month] = monthInput.value.split('-').map(Number);
        const monthEnd = new Date(year, month, 0).getDate();
        range.textContent = `${formatter.format(new Date(year, month - 1, 1))} – ${formatter.format(new Date(year, month - 1, monthEnd))}`;
    }

    period.addEventListener('change', updatePeriodFields);
    dateInput.addEventListener('change', updatePeriodFields);
    dateFrom.addEventListener('change', updatePeriodFields);
    dateTo.addEventListener('change', updatePeriodFields);
    monthInput.addEventListener('change', updatePeriodFields);
    updatePeriodFields();
});
</script>
@endpush
