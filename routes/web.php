<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WalletController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});



require __DIR__.'/auth.php';

Route::post('api/fund-wallet', [ WalletController::class, 'fundWallet']);
Route::post('api/transfer-funds', [WalletController::class, 'transferFunds']);
Route::post('api/create-wallet', [WalletController::class, 'createWallet']);
Route::get('api/users', [WalletController::class, 'getAllUsers']);
Route::get('api/wallets', [WalletController::class, 'getAllWallets']);
Route::get('api/wallet/{id}', [WalletController::class, 'getWalletDetails']);



