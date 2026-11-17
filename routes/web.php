<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;

// Redirect halaman awal ke login
Route::get('/', function () {
    return redirect()->route('login');
});

// Routing Autentikasi
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Routing yang dilindungi Login (Middleware Auth)
Route::middleware('auth')->group(function () {
    
    // Dashboard masing-masing role (Akan di-slicing oleh Anggota 2 - Frontend)
    Route::get('/dashboard/ketua', fn() => view('dashboard.ketua'))->name('dashboard.ketua');
    Route::get('/dashboard/bendahara', fn() => view('dashboard.bendahara'))->name('dashboard.bendahara');
    Route::get('/dashboard/koordiv', fn() => view('dashboard.koordiv'))->name('dashboard.koordiv');
    Route::get('/dashboard/admin', fn() => view('dashboard.admin'))->name('dashboard.admin');

    // Routing CRUD Transaksi lengkap (index, create, store, edit, update, destroy)
    Route::resource('transactions', TransactionController::class);
});