@extends('layouts.app')
@section('title',$expense->exists?'Ubah Pengeluaran':'Catat Pengeluaran')
@section('content')
<style>
    .profit-available-panel{background:linear-gradient(120deg,#f5f2ff,#fbfaff);border:1px solid #e7e1ff;border-radius:14px;padding:18px 20px}
    .profit-available-title{font:700 14px 'Plus Jakarta Sans';color:#302078}.profit-available-subtitle{font-size:11px;color:#858199;margin-top:3px}
    .profit-available-metric{padding:12px 14px;background:#fff;border:1px solid #efedf5;border-radius:10px;height:100%}
    .profit-available-metric small{font-size:11px;color:#77748a}.profit-available-metric strong{display:block;font:800 17px 'Plus Jakarta Sans';color:#292742;margin-top:5px}
    .profit-available-metric.highlight{border-color:#d8ceff}.profit-available-metric.highlight strong{color:#5234c8}
    @media(max-width:575.98px){.profit-available-panel{padding:14px}.profit-available-metric{padding:11px}.profit-available-metric strong{font-size:15px}}
</style>
<div class="card">
    <div class="card-header bg-white p-4"><div class="section-card-title">{{$expense->exists?'Perbarui':'Catat'}} Pengeluaran</div><small class="text-secondary">Gunakan Tarik Semua untuk mengisi seluruh sisa laba yang tersedia.</small></div>
    <div class="p-4 pb-0">
        <div class="profit-available-panel" id="profitPreview" data-url="{{route('expenses.profit-preview')}}" data-exclude-expense="{{$expense->id??''}}">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3"><div><div class="profit-available-title">Ringkasan laba tanggal terpilih</div><div class="profit-available-subtitle">Nilai diperbarui otomatis saat tanggal berubah.</div></div><span class="badge badge-soft" id="previewDateLabel">Tanggal dipilih</span></div>
            <div class="row g-2">
                <div class="col-12 col-sm-4"><div class="profit-available-metric"><small>Laba kotor</small><strong id="previewGross">Memuat...</strong></div></div>
                <div class="col-12 col-sm-4"><div class="profit-available-metric"><small>Pengeluaran sebelumnya</small><strong id="previewExpenses">Memuat...</strong></div></div>
                <div class="col-12 col-sm-4"><div class="profit-available-metric highlight"><small>Sisa laba tersedia</small><strong id="previewAvailable">Memuat...</strong></div></div>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3"><small class="text-secondary">Estimasi laba bersih setelah pengeluaran ini</small><strong id="previewNet" class="text-success">Memuat...</strong></div>
        </div>
    </div>
    <form method="post" action="{{$expense->exists?route('expenses.update',$expense):route('expenses.store')}}">
        @csrf @if($expense->exists) @method('put') @endif
        <div class="card-body p-4"><div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="expenseDate">Tanggal *</label><input class="form-control" id="expenseDate" type="date" name="expense_date" value="{{old('expense_date',$expense->expense_date?->format('Y-m-d')??today()->format('Y-m-d'))}}" required></div>
            <div class="col-md-8"><label class="form-label" for="expenseAmount">Jumlah pengeluaran (Rp) *</label><div class="input-group"><span class="input-group-text bg-light">Rp</span><input class="form-control" id="expenseAmount" type="text" inputmode="numeric" autocomplete="off" name="amount" value="{{old('amount',$expense->exists?number_format((float)$expense->amount,0,',','.'):'')}}" placeholder="0" required><button class="btn btn-outline-primary text-nowrap" type="button" id="withdrawAll" disabled><i class="fa-solid fa-arrow-down me-1"></i>Tarik Semua</button></div></div>
            <div class="col-12"><label class="form-label" for="expenseDescription">Keterangan *</label><input class="form-control" id="expenseDescription" name="description" maxlength="180" value="{{old('description',$expense->description)}}" placeholder="Contoh: Biaya listrik, ongkir, atau kebutuhan toko" required></div>
        </div></div>
        <div class="card-footer bg-white p-4 d-flex justify-content-end gap-2"><a class="btn btn-light" href="{{route('expenses.index')}}">Batal</a><button class="btn btn-primary"><i class="fa-solid fa-check me-2"></i>Simpan Pengeluaran</button></div>
    </form>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const preview=document.getElementById('profitPreview');
    const date=document.getElementById('expenseDate');
    const amount=document.getElementById('expenseAmount');
    const grossElement=document.getElementById('previewGross');
    const expensesElement=document.getElementById('previewExpenses');
    const availableElement=document.getElementById('previewAvailable');
    const netElement=document.getElementById('previewNet');
    const withdrawButton=document.getElementById('withdrawAll');
    const dateLabel=document.getElementById('previewDateLabel');
    const money=new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0});
    const numberFormat=new Intl.NumberFormat('id-ID',{maximumFractionDigits:0});
    let gross=0,otherExpenses=0,requestNumber=0;
    function formatAmount(value){const digits=String(value??'').replace(/\D/g,'');return digits?numberFormat.format(Number(digits)):'';}
    function updateNet(){
        const enteredAmount=Number(amount.value.replace(/\D/g,''))||0;
        const net=gross-otherExpenses-enteredAmount;
        netElement.textContent=money.format(net);
        netElement.classList.toggle('text-danger',net<0);
        netElement.classList.toggle('text-success',net>=0);
    }
    async function loadPreview(){
        if(!date.value)return;
        const currentRequest=++requestNumber;gross=0;otherExpenses=0;updateNet();
        grossElement.textContent='Memuat...';expensesElement.textContent='Memuat...';availableElement.textContent='Memuat...';withdrawButton.disabled=true;
        const params=new URLSearchParams({date:date.value});
        if(preview.dataset.excludeExpense)params.set('exclude_expense',preview.dataset.excludeExpense);
        try{
            const response=await fetch(`${preview.dataset.url}?${params.toString()}`,{headers:{'Accept':'application/json'}});
            if(!response.ok)throw new Error('Gagal memuat ringkasan.');
            const data=await response.json();if(currentRequest!==requestNumber)return;
            gross=Number(data.gross_profit)||0;otherExpenses=Number(data.other_expenses)||0;
            const available=Math.max(0,gross-otherExpenses);
            grossElement.textContent=money.format(gross);expensesElement.textContent=money.format(otherExpenses);availableElement.textContent=money.format(available);
            dateLabel.textContent=new Intl.DateTimeFormat('id-ID',{day:'numeric',month:'long',year:'numeric'}).format(new Date(`${date.value}T00:00:00`));
            withdrawButton.disabled=available<=0;updateNet();
        }catch(error){
            if(currentRequest!==requestNumber)return;
            grossElement.textContent='Tidak tersedia';expensesElement.textContent='Tidak tersedia';availableElement.textContent='Tidak tersedia';netElement.textContent='Coba muat ulang';
        }
    }
    withdrawButton.addEventListener('click',()=>{
        amount.value=numberFormat.format(Math.round(Math.max(0,gross-otherExpenses)));
        amount.dispatchEvent(new Event('input',{bubbles:true}));
    });
    date.addEventListener('change',loadPreview);amount.addEventListener('input',()=>{const cursor=amount.selectionStart;const before=amount.value.slice(0,cursor).replace(/\D/g,'').length;amount.value=formatAmount(amount.value);let position=0,count=0;while(position<amount.value.length&&count<before){if(/\d/.test(amount.value[position]))count++;position++;}amount.setSelectionRange(position,position);updateNet();});
    amount.value=formatAmount(amount.value);loadPreview();
});
</script>
@endpush
@endsection
