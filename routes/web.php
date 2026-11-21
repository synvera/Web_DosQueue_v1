<?php

use Illuminate\Support\Facades\Route;

// Halaman Pengunjung (Guest)
Route::get('/', function () {
    return view('guest.index');
})->name('guest.index');

Route::get('/tiket/{token}', function (string $token) {
    return view('guest.ticket', ['token' => $token]);
})->name('guest.ticket');

// Halaman Petugas (Staff)
Route::get('/staff/login', function () {
    return view('staff.login');
})->name('staff.login');

Route::get('/staff/dashboard', function () {
    return view('staff.dashboard');
})->name('staff.dashboard');