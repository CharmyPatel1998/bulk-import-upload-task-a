<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;


Route::post('/uploads/chunk', [UploadController::class, 'uploadChunk']);
Route::post('/uploads/complete', [UploadController::class, 'completeUpload']);
