<?php
use App\Http\Controllers\{ProfileController,DashboardController,MasterDataController,StockTransactionController,SalesReturnController,ReportController,UserManagementController};
use Illuminate\Support\Facades\Route;
Route::redirect('/', '/dashboard');
Route::middleware('auth')->group(function(){
 Route::get('/dashboard',DashboardController::class)->name('dashboard');
 Route::get('/profile',[ProfileController::class,'edit'])->name('profile.edit');Route::patch('/profile',[ProfileController::class,'update'])->name('profile.update');Route::delete('/profile',[ProfileController::class,'destroy'])->name('profile.destroy');
 Route::middleware('role:Super Admin,Admin')->group(function(){
  Route::resource('products',MasterDataController::class)->parameters(['products'=>'product']);
  Route::get('/stock/inbound',[StockTransactionController::class,'inbound'])->name('stock.inbound');Route::post('/stock/inbound',[StockTransactionController::class,'storeInbound'])->name('stock.inbound.store');Route::get('/reports/profit',[ReportController::class,'profit'])->name('reports.profit');Route::get('/reports/profit/download/{format}',[ReportController::class,'exportProfit'])->name('reports.profit.export');Route::get('/reports/print/{type}',[ReportController::class,'print'])->name('reports.print');Route::get('/reports/export/{type}/{format}',[ReportController::class,'export'])->name('reports.export');Route::get('/reports/{type?}',ReportController::class)->name('reports');
 });
 Route::middleware('role:Super Admin')->group(function(){Route::get('/users',[UserManagementController::class,'index'])->name('users.index');Route::post('/users',[UserManagementController::class,'store'])->name('users.store');Route::put('/users/{user}',[UserManagementController::class,'update'])->name('users.update');Route::delete('/users/{user}',[UserManagementController::class,'destroy'])->name('users.destroy');});
 Route::middleware('role:Super Admin,Admin,Kasir')->group(function(){Route::get('/stock/outbound',[StockTransactionController::class,'outbound'])->name('stock.outbound');Route::post('/stock/outbound',[StockTransactionController::class,'storeOutbound'])->name('stock.outbound.store');Route::get('/sales/history',[StockTransactionController::class,'salesHistory'])->name('sales.history');Route::get('/sales/returns',[SalesReturnController::class,'history'])->name('sales.returns.history');Route::get('/sales/{sale}/return',[SalesReturnController::class,'create'])->whereNumber('sale')->name('sales.returns.create');Route::post('/sales/{sale}/return',[SalesReturnController::class,'store'])->whereNumber('sale')->name('sales.returns.store');});
 Route::middleware('role:Super Admin,Admin')->get('/stock/history',[StockTransactionController::class,'history'])->name('stock.history');
});
require __DIR__.'/auth.php';
