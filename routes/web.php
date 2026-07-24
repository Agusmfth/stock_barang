<?php
use App\Http\Controllers\{ProfileController,DashboardController,MasterDataController,StockTransactionController,ReportController,UserManagementController};
use Illuminate\Support\Facades\Route;
Route::redirect('/', '/dashboard');
Route::middleware('auth')->group(function(){
 Route::get('/dashboard',DashboardController::class)->name('dashboard');
 Route::get('/profile',[ProfileController::class,'edit'])->name('profile.edit');Route::patch('/profile',[ProfileController::class,'update'])->name('profile.update');Route::delete('/profile',[ProfileController::class,'destroy'])->name('profile.destroy');
 Route::resource('products',MasterDataController::class)->parameters(['products'=>'product']);Route::resource('suppliers',MasterDataController::class)->parameters(['suppliers'=>'supplier']);Route::resource('warehouses',MasterDataController::class)->parameters(['warehouses'=>'warehouse']);Route::resource('salespeople',MasterDataController::class)->parameters(['salespeople'=>'salesperson']);
 Route::middleware('role:Super Admin')->group(function(){Route::get('/users',[UserManagementController::class,'index'])->name('users.index');Route::post('/users',[UserManagementController::class,'store'])->name('users.store');Route::put('/users/{user}',[UserManagementController::class,'update'])->name('users.update');Route::delete('/users/{user}',[UserManagementController::class,'destroy'])->name('users.destroy');});
 Route::get('/stock/inbound',[StockTransactionController::class,'inbound'])->name('stock.inbound');Route::post('/stock/inbound',[StockTransactionController::class,'storeInbound'])->name('stock.inbound.store');Route::get('/stock/outbound',[StockTransactionController::class,'outbound'])->name('stock.outbound');Route::post('/stock/outbound',[StockTransactionController::class,'storeOutbound'])->name('stock.outbound.store');Route::get('/stock/history',[StockTransactionController::class,'history'])->name('stock.history');Route::get('/reports/{type?}',ReportController::class)->name('reports');
});
require __DIR__.'/auth.php';
