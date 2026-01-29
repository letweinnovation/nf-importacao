<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ImportController;

Route::get('/', [ImportController::class, 'index'])->name('home');

Route::post('/process/{type}', [ImportController::class, 'update'])->name('process.files');
Route::get('/history', [ImportController::class, 'getHistory'])->name('history');
Route::get('/download/{filename}', [ImportController::class, 'downloadFile'])->name('download.file');

