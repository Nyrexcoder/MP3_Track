<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/mp3', [DashboardController::class, 'store'])->name('mp3.store');
    Route::get('/dashboard/stream/{id}', [DashboardController::class, 'stream'])->name('mp3.stream');
    Route::delete('/dashboard/mp3/{id}', [DashboardController::class, 'destroy'])->name('mp3.destroy');

    Route::post('/dashboard/folder', [DashboardController::class, 'createFolder'])->name('folder.create');
    Route::post('/dashboard/folder/{id}/unlock', [DashboardController::class, 'unlockFolder'])->name('folder.unlock');
    Route::delete('/dashboard/folder/{id}', [DashboardController::class, 'destroyFolder'])->name('folder.destroy');
    Route::get('/dashboard/queue-status', [DashboardController::class, 'queueStatus'])->name('dashboard.queue-status');
    Route::post('/dashboard/bulk-action', [DashboardController::class, 'bulkAction'])->name('dashboard.bulk-action');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
