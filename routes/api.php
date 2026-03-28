<?php

use App\Http\Controllers\Mp3Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('mp3s')->group(function () {
    Route::get('/', [Mp3Controller::class, 'index']);
    Route::post('/', [Mp3Controller::class, 'store']);
    Route::get('/{id}', [Mp3Controller::class, 'show']);
    Route::delete('/{id}', [Mp3Controller::class, 'destroy']);
});
