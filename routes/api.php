<?php

use App\Http\Controllers\AccurateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/accurate/stock', [AccurateController::class, 'GetAllStockAccurate']);
