<?php
use App\Http\Controllers\{ProfileController,DashboardController,MasterDataController,StockTransactionController,ReportController};
use Illuminate\Support\Facades\Route;
Route::redirect('/', '/dashboard');
Route::middleware('auth')->group(function(){
 Route::get('/dashboard',DashboardController::class)->name('dashboard');
 Route::get('/profile',[ProfileController::class,'edit'])->name('profile.edit');Route::patch('/profile',[ProfileController::class,'update'])->name('profile.update');Route::delete('/profile',[ProfileController::class,'destroy'])->name('profile.destroy');
 Route::resource('products',MasterDataController::class)->except('show')->parameters(['products'=>'product']);Route::resource('suppliers',MasterDataController::class)->except('show')->parameters(['suppliers'=>'supplier']);Route::resource('warehouses',MasterDataController::class)->except('show')->parameters(['warehouses'=>'warehouse']);Route::resource('salespeople',MasterDataController::class)->except('show')->parameters(['salespeople'=>'salesperson']);
 Route::get('/stock/inbound',[StockTransactionController::class,'inbound'])->name('stock.inbound');Route::post('/stock/inbound',[StockTransactionController::class,'storeInbound'])->name('stock.inbound.store');Route::get('/stock/outbound',[StockTransactionController::class,'outbound'])->name('stock.outbound');Route::post('/stock/outbound',[StockTransactionController::class,'storeOutbound'])->name('stock.outbound.store');Route::get('/stock/history',[StockTransactionController::class,'history'])->name('stock.history');Route::get('/reports/{type?}',ReportController::class)->name('reports');
});
require __DIR__.'/auth.php';
