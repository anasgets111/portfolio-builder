<?php

use App\Http\Controllers\CvController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/cv', CvController::class)->name('cv.show');
