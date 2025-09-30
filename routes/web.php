<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/upload-image', [UploadController::class, 'showForm'])->name('upload.form');
Route::post('/upload-image-store', [UploadController::class, 'store'])->name('upload.store');
